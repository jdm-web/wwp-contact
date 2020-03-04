<?php

namespace WonderWp\Plugin\Contact\Service;

use Respect\Validation\Validator;
use WonderWp\Component\Form\Field\EmailField;
use WonderWp\Component\Form\Field\FieldInterface;
use WonderWp\Component\Form\Field\FileField;
use WonderWp\Component\Form\Field\PhoneField;
use WonderWp\Component\HttpFoundation\Request;
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
    /**
     * @param FormInterface     $formInstance
     * @param ContactFormEntity $formItem
     * @param array             $values
     *
     * @return FormInterface
     */
    public function fillFormInstanceFromItem(FormInterface $formInstance, ContactFormEntity $formItem, ContactFormFieldRepository $contactFormFieldrepository, array $values = [], Request $request = null)
    {
        global $post, $wp_query;
    
        $postId = 0;
        if($wp_query->post_count == 1){
            $postId = $post->ID;
        }
        
        // Form id
        $formId = $formItem->getId();

        // Add configured fields
        $configuredFields = json_decode($formItem->getData(), true);

        if (!empty($configuredFields)) {

            foreach ($configuredFields as $fieldId => $fieldOptions) {
                /** @var ContactFormFieldEntity $field */
                $fieldEntity = $contactFormFieldrepository->find($fieldId);
                if ($fieldEntity instanceof ContactFormFieldEntity) {
                    $formField = $this->generateDefaultField($formId, $fieldEntity, $fieldOptions);
                    $formInstance->addField($formField);
                }
            }
        }

        $extraFields = $this->getOtherNecessaryFields($formItem, $postId, $request);
        if (!empty($extraFields)) {
            $extraFields = apply_filters('wwp-contact.contact_form.extra_fields', $extraFields, $formItem);
            foreach ($extraFields as $extraField) {
                $formInstance->addField($extraField);
            }
        }

        $formInstance = apply_filters(
            'wwp-contact.contact_form.created',
            $formInstance,
            $formItem
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
            $currentLocale = get_locale();
            $choices       = ['' => __('choose.subject.trad', WWP_CONTACT_TEXTDOMAIN)];
            foreach ($field->getOption('choices', []) as $choice) {
                if (!isset($choice['locale'])) {
                    $choice['locale'] = $currentLocale;
                }
                if ($choice['locale'] === $currentLocale) {
                    $choices[$choice['value']] = stripslashes($choice['label']);
                }
            }
            $fieldInstance->setOptions($choices);
        }

        return $fieldInstance;
    }

    protected function computeDisplayRules($formId, ContactFormFieldEntity $field, $fieldClass)
    {
        // Get translation keys
        $label       = $this->getTranslation($formId, $field->getName());
        $help        = $this->getTranslation($formId, $field->getName(), 'help', false);
        $placeHolder = $this->getTranslation($formId, $field->getName(), 'placeholder', false);

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

        return $displayRules;
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

        return $validationRules;
    }

    /**
     * @param ContactFormEntity $formItem
     * @param \WP_Post|null     $post
     *
     * @return array
     */
    public function getOtherNecessaryFields(ContactFormEntity $formItem, $postId = 0, Request $request=null)
    {
        // Add other necessary fields

        $extraFields = [
            'form'     => new HiddenField('form', $formItem->getId(), ['inputAttributes' => ['id' => 'form-' . $formItem->getId()]]),
            'nonce'    => new NonceField('nonce', null, ['inputAttributes' => ['id' => 'nonce-' . $formItem->getId()]]),
            'honeypot' => new HoneyPotField(HoneyPotField::HONEYPOT_FIELD_NAME, null, ['inputAttributes' => ['id' => HoneyPotField::HONEYPOT_FIELD_NAME . '-' . $formItem->getId()]]),
        ];
        
        //if no post given error in saving contact form
        $extraFields['post'] = new HiddenField('post', $postId, ['inputAttributes' => ['id' => 'post-' . $formItem->getId()]]);
        
        if($request){
            $urlSrc = $request->getSchemeAndHttpHost().$request->getRequestUri();
            $extraFields['srcpage']  = new HiddenField('srcpage', $urlSrc, ['inputAttributes' => ['id' => 'srcpage-'.$formItem->getId()]]);
        }
        
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
    public function getTranslation($formId, $fiedName, $key = null, $required = true, $strict = false)
    {
        // Init
        $suffix      = (null !== $key) ? '.' . $key . '.trad' : '.trad';
        $translation = __($fiedName . $suffix, WWP_CONTACT_TEXTDOMAIN);

        // Hierarchie
        $translationWithId = __($fiedName . '.' . $formId . $suffix, WWP_CONTACT_TEXTDOMAIN);

        if ($fiedName . '.' . $formId . $suffix != $translationWithId) {
            $translation = $translationWithId;
        } elseif ($fiedName . $suffix != $translation) {
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
     * @param array             $values
     *
     * @return array
     */
    public function prepareViewParams(ContactFormEntity $formItem = null, array $values = [], Request $request = null)
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
        $formInstance               = $this->fillFormInstanceFromItem(Container::getInstance()->offsetGet('wwp.form.form'), $formItem, $contactFormFieldrepository, $values, $request);
        $formInstance->setName('contactForm');
        $formView     = $this->getViewFromFormInstance($formInstance);
        $viewParams   = [
            'item'     => $formItem,
            'instance' => $formInstance,
            'view'     => $formView,
        ];
        $formViewOpts = [
            'formStart' => [
                'action'     => '/contactFormSubmit',
                'data-form'  => $formItem->getId(),
                'data-title' => __('form.' . $formItem->getId() . '.titre.trad'),
            ],
            'formEnd'   => [
                'submitLabel' => __('submit', WWP_CONTACT_TEXTDOMAIN),
            ],
        ];
        // Text intro
        $introTrad = $this->getTranslation($formItem->getId(), 'form', 'intro', false, true);

        if (false === $introTrad && current_user_can('manage_options')) {
            $introTrad = "<span class=\"help\">Message pour l'administrateur : le texte d'intro du formulaire peut être administré via les clés : <strong>form." . $formItem->getId() . ".intro.trad</strong> ou <strong>form.intro.trad</strong>.</span>";
        }

        if (false !== $introTrad) {
            $formViewOpts['formBeforeFields'][] = wp_sprintf($introTrad, $formItem->getNumberOfDaysBeforeRemove());
        }
        $viewParams['viewOpts'] = $formViewOpts;

        return $viewParams;
    }
}
