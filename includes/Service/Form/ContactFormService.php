<?php

namespace WonderWp\Plugin\Contact\Service\Form;

use Respect\Validation\Validator;
use WonderWp\Component\Form\Field\EmailField;
use WonderWp\Component\Form\Field\FieldGroup;
use WonderWp\Component\Form\Field\FieldInterface;
use WonderWp\Component\Form\Field\FileField;
use WonderWp\Component\Form\Field\PhoneField;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Service\AbstractService;
use function WonderWp\Functions\array_merge_recursive_distinct;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\AbstractField;
use WonderWp\Component\Form\Field\HiddenField;
use WonderWp\Component\Form\Field\HoneyPotField;
use WonderWp\Component\Form\Field\NonceField;
use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\Form\FormViewInterface;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;

class ContactFormService extends AbstractService
{
    const formFieldKey     = 'form';
    const nonceFieldKey    = 'nonce';
    const honeypotFieldKey = 'honeypot';
    const postFieldKey     = 'post';

    /**
     * @param FormInterface $formInstance
     * @param ContactFormEntity $formItem
     * @param ContactFormFieldRepository $contactFormFieldrepository
     * @param array $values
     * @param array $allowedExtraFields
     * @return FormInterface
     */
    public function fillFormInstanceFromItem(
        FormInterface              $formInstance,
        ContactFormEntity          $formItem,
        ContactFormFieldRepository $contactFormFieldrepository,
        array                      $values = [],
        array                      $allowedExtraFields = []
    )
    {
        global $post, $wp_query;

        $postId = 0;
        if ($wp_query->post_count == 1) {
            $postId = $post->ID;
        }

        // Form id
        $formId = $formItem->getId();

        // Add configured fields
        $configuredFields = json_decode($formItem->getData(), true);

        if (!empty($configuredFields)) {

            //traitement par groupe, si on a des infos de groupes dans le champ data et si on a plus d'un groupe de champs
            if (isset($configuredFields["fields"]) && isset($configuredFields["groups"]) && count($configuredFields["groups"]) > 1) {
                $cpt = 1;
                foreach ($configuredFields["groups"] as $id_group => $group) {
                    $listFields = [];
                    $labelRef   = sanitize_title($group["label"]);
                    $group_name = "g" . $id_group;

                    //recup??re tous les champs de chaque groupe pour insertion dans le form
                    foreach ($configuredFields["fields"] as $id_field => $field) {
                        if ((int)$field["group"] == $id_group) {
                            $listFields[$id_field]    = $field;
                            $treatedFields[$id_field] = true;
                        }
                    }

                    //insertion du groupe de champs dans le form
                    $fieldGroup = $this->generateGroupField($group_name, $listFields, $labelRef, $contactFormFieldrepository, $formId, $cpt);
                    $formInstance->addField($fieldGroup);
                    $cpt++;
                }
            } else {
                //si on a un seul groupe, on recupere les champs => pas de gestion de la notion de groupe
                if (isset($configuredFields["fields"])) {
                    $configuredFields = $configuredFields["fields"];
                }

                foreach ($configuredFields as $fieldId => $fieldOptions) {
                    $formField = $this->generateField($fieldId, $fieldOptions, $contactFormFieldrepository, $formId);
                    $formInstance->addField($formField);
                }
            }
        }

        // Add other necessary fields
        $extraFields = $this->getOtherNecessaryFields($formItem, $allowedExtraFields, $postId);
        if (!empty($extraFields)) {
            $extraFields = apply_filters('wwp-contact.contact_form.extra_fields', $extraFields, $formItem);
            foreach ($extraFields as $extraField) {
                $formInstance->addField($extraField);
            }
        }

        $formInstance = apply_filters(
            'wwp-contact.contact_form.created',
            $formInstance,
            $formItem,
            $values
        );

        if (!empty($values)) {
            $formDefaultValues = [];
            foreach ($formInstance->getFields() as $f) {
                /** @var FieldInterface $f */
                $formDefaultValues[$f->getName()] = $f->getValue();
            }
            $formInstance->fill(array_merge_recursive_distinct($formDefaultValues, $values));
        }

        return $formInstance;
    }

    private function generateGroupField($group_name, $listFields, $labelRef, $contactFormFieldrepository, $formId, $index)
    {
        $label        = self::getTranslation($formId, 'group.' . $labelRef, null, false);
        $displayRules = [
            'inputAttributes' => [
                'class' => ['form-group-wrap'],
            ],
            'wrapAttributes'  => [
                'class'      => ['group-wrap', 'group-' . $labelRef . '-wrap'],
                'data-index' => $index,
            ],
        ];
        if (!empty($label)) {
            $displayRules['label'] = $label;
        }

        $fieldGroup = new FieldGroup($group_name, null, $displayRules);

        foreach ($listFields as $fieldId => $fieldData) {
            $field = $this->generateField($fieldId, $fieldData, $contactFormFieldrepository, $formId);
            if (!empty($field)) {
                $fieldGroup->addFieldToGroup($field);
            }
        }

        return $fieldGroup;
    }

    private function generateField($fieldId, $fieldOptions, $contactFormFieldrepository, $formId)
    {

        $formField   = null;
        $fieldEntity = $contactFormFieldrepository->find($fieldId);
        if ($fieldEntity instanceof ContactFormFieldEntity) {
            $formField = $this->generateDefaultField($formId, $fieldEntity, $fieldOptions);
        }

        return $formField;
    }

    /**
     * @param                        $formId
     * @param ContactFormFieldEntity $field
     * @param                        $fieldOptions
     *
     * @return null|AbstractField
     */
    private function generateDefaultField($formId, ContactFormFieldEntity $field, $fieldOptions)
    {
        $fieldClass      = str_replace('\\\\', '\\', $field->getType());
        $displayRules    = $this->computeDisplayRules($formId, $field, $fieldClass);
        $validationRules = $this->computeValidationRules($field, $fieldClass, $fieldOptions);
        $fieldInstance   = new $fieldClass($field->getName(), null, $displayRules, $validationRules);

        if ($fieldInstance instanceof SelectField) {
            $currentLocale    = get_locale();
            $firstChoiceLabel = self::getTranslation($formId, $field->getName(), 'placeholder', false);
            if (empty($firstChoiceLabel)) {
                $firstChoiceLabel = __('choose.subject.trad', WWP_CONTACT_TEXTDOMAIN);
            }
            $choices = ['' => $firstChoiceLabel];

            foreach ($field->getOption('choices', []) as $choice) {
                if (!isset($choice['locale'])) {
                    $choice['locale'] = $currentLocale;
                }
                if ($choice['locale'] === $currentLocale) {
                    $choices[$choice['value']] = stripslashes($choice['label']);
                }
            }
            $fieldInstance->setOptions(apply_filters('wwp-contact.contact_form.select_field.options', $choices, $field, $formId));
        }

        return $fieldInstance;
    }

    protected function computeDisplayRules($formId, ContactFormFieldEntity $field, $fieldClass)
    {
        // Get translation keys
        $label       = self::getTranslation($formId, $field->getName());
        $help        = self::getTranslation($formId, $field->getName(), 'help', false);
        $placeHolder = self::getTranslation($formId, $field->getName(), 'placeholder', false);

        $displayRules = [
            'label'           => $label,
            'help'            => $help,
            'inputAttributes' => [
                'id' => $field->getName() . '-' . $formId,
            ],
        ];

        if ($fieldClass === FileField::class) {
            $allowedExtensions = $field->getOption('extensions');
            if (!empty($allowedExtensions)) {
                $allowedExtensionsFrags = explode(',', $allowedExtensions);
                if (!empty($allowedExtensionsFrags)) {
                    $accepts = [];
                    foreach ($allowedExtensionsFrags as $ext) {
                        $accepts[] = '.' . str_replace([' ', '.'], '', $ext);
                    }
                    $displayRules['inputAttributes']['accept'] = implode(',', $accepts);
                }
            }
        }

        if (false !== $placeHolder) {
            $displayRules['inputAttributes']['placeholder'] = $placeHolder;
        }

        $autoComplete = $field->getOption('autocomplete');
        if (!empty($autoComplete)) {
            $displayRules['inputAttributes']['autocomplete'] = stripslashes($autoComplete);
        }

        return apply_filters('wwp-contact.contact_form.field.display_rules', $displayRules, $field, $formId);
    }

    protected function computeValidationRules(ContactFormFieldEntity $field, $fieldClass, $fieldOptions)
    {
        $validationRules = [];
        if ($field->isRequired($fieldOptions)) {
            $validationRules[] = Validator::notEmpty();
        }
        if ($fieldClass === PhoneField::class) {
            $validationRules[] = Validator::phone();
        }
        if ($fieldClass === EmailField::class) {
            $validationRules[] = Validator::email();
        }
        if ($fieldClass === FileField::class) {
            $allowedExtensions = $field->getOption('extensions');
            if (!empty($allowedExtensions)) {
                $allowedExtensionsFrags = explode(',', $allowedExtensions);
                if (!empty($allowedExtensionsFrags)) {
                    $accepts = [];
                    foreach ($allowedExtensionsFrags as $ext) {
                        $accepts[] = Validator::extension(str_replace([' ', '.'], '', $ext));
                    }
                    $validationRules[] = Validator::oneOf(...$accepts);
                }
            }
        }

        $maxLength = $field->getOption('maxlength');
        if (!empty($maxLength)) {
            $validationRules[] = Validator::length(null, $maxLength);
        }

        return apply_filters('wwp-contact.contact_form.field.validation_rules', $validationRules, $field);
    }

    /**
     * @param ContactFormEntity $formItem
     * @param array $allowedExtraFields
     * @param int $postId
     *
     * @return array
     */
    public function getOtherNecessaryFields(ContactFormEntity $formItem, array $allowedExtraFields = [], $postId = 0)
    {
        $extraFields = [];

        if (empty($allowedExtraFields)) {
            $allowedExtraFields = self::getDefaultAllowedFields();
        }

        if (in_array(static::formFieldKey, $allowedExtraFields)) {
            $extraFields[static::formFieldKey] = new HiddenField('form', $formItem->getId(), ['inputAttributes' => ['id' => 'form-' . $formItem->getId()]]);
        }
        if (in_array(static::nonceFieldKey, $allowedExtraFields)) {
            $extraFields[static::nonceFieldKey] = new NonceField('nonce', null, ['inputAttributes' => ['id' => 'nonce-' . $formItem->getId()]]);
        }
        if (in_array(static::honeypotFieldKey, $allowedExtraFields)) {
            $extraFields[static::honeypotFieldKey] = new HoneyPotField(HoneyPotField::HONEYPOT_FIELD_NAME, null, ['inputAttributes' => ['id' => HoneyPotField::HONEYPOT_FIELD_NAME . '-' . $formItem->getId()]]);
        }
        if (in_array(static::postFieldKey, $allowedExtraFields)) {
            $extraFields[static::postFieldKey] = new HiddenField('post', $postId, ['inputAttributes' => ['id' => 'post-' . $formItem->getId()]]);
        }
        /*if ($request) {
            $urlSrc                 = $request->getSchemeAndHttpHost() . $request->getRequestUri();
            $extraFields['srcpage'] = new HiddenField('srcpage', $urlSrc, ['inputAttributes' => ['id' => 'srcpage-' . $formItem->getId()]]);
        }*/

        return $extraFields;
    }

    /**
     * @param FormInterface $form
     *
     * @return FormViewInterface
     */
    public function getViewFromFormInstance(FormInterface $form)
    {
        return $form->getView();
    }

    /**
     * @param integer
     * @param string
     * @param string
     * @param bool
     * @param bool
     *
     * @return string|bool
     */
    public static function getTranslation($formId, $fieldName, $key = null, $required = true, $strict = false)
    {
        // Init
        $suffix      = (null !== $key) ? '.' . $key . '.trad' : '.trad';
        $translation = __($fieldName . $suffix, WWP_CONTACT_TEXTDOMAIN);

        // Hierarchie
        $translationWithId = __($fieldName . '.' . $formId . $suffix, WWP_CONTACT_TEXTDOMAIN);

        if ($fieldName . '.' . $formId . $suffix != $translationWithId) {
            $translation = $translationWithId;
        } elseif ($fieldName . $suffix != $translation) {
            //$translation = $translation;
        } elseif (false === $required) {
            $translation = false;
        } elseif (true === $required && true === $strict) {
            $translation = $translationWithId;
        }

        // Result
        return $translation;
    }

    /**
     * @param ContactFormEntity $formItem
     * @param array $values
     *
     * @return array
     * @throws ServiceNotFoundException
     */
    public function prepareViewParams(ContactFormEntity $formItem = null, array $values = [], array $allowedExtraFields = [])
    {
        if (empty($formItem)) {
            return [
                'item'         => null,
                'instance'     => null,
                'view'         => null,
                'view-options' => [],
            ];
        }

        /** @var ContactFormFieldRepository $contactFormFieldrepository */
        $contactFormFieldrepository = $this->manager->getService('formFieldRepository');
        $formInstance               = $this->fillFormInstanceFromItem(Container::getInstance()->offsetGet('wwp.form.form'), $formItem, $contactFormFieldrepository, $values, $allowedExtraFields);
        $formInstance->setName('contactForm');
        $formView   = $this->getViewFromFormInstance($formInstance);
        $viewParams = [
            'item'     => $formItem,
            'instance' => $formInstance,
            'view'     => $formView,
        ];

        $translateKey = 'form.' . $formItem->getId() . '.titre.trad';
        $title        = __($translateKey, WWP_CONTACT_TEXTDOMAIN);

        $submitLabel = self::getTranslation($formItem->getId(), 'form', 'submitLabel', false, true);

        $formViewOpts = [
            'formStart' => [
                'action'     => '/contactFormSubmit',
                'data-form'  => $formItem->getId(),
                'data-title' => $translateKey !== $title ? esc_attr($title) : $formItem->getName(),
                'class'      => ['wwpform', 'contactForm', 'contactForm-' . $formItem->getId()],
            ],
            'formEnd'   => [
                'submitLabel' => $submitLabel !== false ? $submitLabel : __('submit', WWP_CONTACT_TEXTDOMAIN),
            ],
        ];

        //Check if form has groups
        $configuredFields = json_decode($formItem->getData(), true);
        if (!empty($configuredFields) && isset($configuredFields["groups"]) && count($configuredFields["groups"]) > 1) {
            $formViewOpts['formStart']['class'][] = 'has-groups';
        }

        // Text intro
        $introTrad = self::getTranslation($formItem->getId(), 'form', 'intro', false, true);

        if (false === $introTrad && current_user_can('manage_options')) {
            $introTrad = "<span class=\"help\">Message pour l'administrateur : le texte d'intro du formulaire peut ??tre administr?? via les cl??s : <strong>form." . $formItem->getId() . ".intro.trad</strong> ou <strong>form.intro.trad</strong>.</span>";
        }

        if (false !== $introTrad) {
            $formViewOpts['formBeforeFields'][] = wp_sprintf($introTrad, $formItem->getNumberOfDaysBeforeRemove());
        }
        $viewParams['viewOpts'] = $formViewOpts;

        return $viewParams;
    }

    public static function getDefaultAllowedFields()
    {
        return [
            static::formFieldKey,
            static::nonceFieldKey,
            static::honeypotFieldKey,
            static::postFieldKey
        ];
    }
}
