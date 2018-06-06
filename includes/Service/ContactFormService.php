<?php

namespace WonderWp\Plugin\Contact\Service;

use Doctrine\ORM\EntityManager;
use Respect\Validation\Validator;
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

class ContactFormService
{
    /**
     * @param ContactFormEntity $formItem
     * @param array             $values
     *
     * @return FormInterface
     */
    public function getFormInstanceFromItem(ContactFormEntity $formItem, array $values = [])
    {
        global $post;
        /** @var FormInterface $formInstance */
        $formInstance = Container::getInstance()->offsetGet('wwp.form.form');

        // Form id
        $formId = $formItem->getId();

        // Add configured fields
        $data = json_decode($formItem->getData(), true);
        if (!empty($data)) {
            foreach ($data as $fieldId => $fieldOptions) {
                $field = $this->generateDefaultField($formId, $fieldId, $fieldOptions);
                $formInstance->addField($field);
            }
        }

        $this->addOtherNecessaryFields($formItem, $formInstance, $post);

        $formInstance = apply_filters(
            'wwp-contact.contact_form.created',
            $formInstance,
            $formItem
        );

        if (!empty($values)) {
            $formDefaultValues = [];
            foreach ($formInstance->getFields() as $f) {
                $formDefaultValues[$f->getName()] = $f->getValue();
            }
            $formInstance->fill(array_merge_recursive_distinct($formDefaultValues, $values));
        }

        return $formInstance;
    }

    /**
     * @param string $fieldId
     * @param array  $fieldOptions
     *
     * @return null|AbstractField
     */
    private function generateDefaultField($formId, $fieldId, $fieldOptions)
    {
        /** @var EntityManager $em */
        $em = Container::getInstance()->offsetGet('entityManager');
        $field = $em->getRepository(ContactFormFieldEntity::class)->find($fieldId);

        if (!$field instanceof ContactFormFieldEntity) {
            return null;
        }

        // Get translation keys
        $label = $this->getTranslation($formId, $field->getName());
        $help = $this->getTranslation($formId, $field->getName(), 'help', false);
        $placeHolder = $this->getTranslation($formId, $field->getName(), 'placeholder', false);

        $displayRules = [
            'label' => $label,
            'help' => $help,
            'inputAttributes' => [],
        ];

        if (false !== $placeHolder) {
            $displayRules['inputAttributes']['placeholder'] = $placeHolder;
        }

        // Validation
        $validationRules = [];
        if ($field->isRequired($fieldOptions)) {
            $validationRules[] = Validator::notEmpty();
        }

        $fieldClass = str_replace('\\\\', '\\', $field->getType());
        $fieldInstance = new $fieldClass($field->getName(), null, $displayRules, $validationRules);

        if ($fieldInstance instanceof SelectField) {
            $currentLocale = get_locale();
            $choices = ['' => __('choose.subject.trad', WWP_CONTACT_TEXTDOMAIN)];
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

    protected function addOtherNecessaryFields(ContactFormEntity $formItem, FormInterface $formInstance, \WP_Post $post = null)
    {
        // Add other necessary fields

        $extraFields = [
            'form' => new HiddenField('form', $formItem->getId()),
            'nonce' => new NonceField('nonce'),
            'honeypot' => new HoneyPotField(HoneyPotField::HONEYPOT_FIELD_NAME),
        ];

        if ($post) {
            $extraFields['post'] = new HiddenField('post', $post->ID);
        }

        $extraFields = apply_filters('wwp-contact.contact_form.extra_fields', $extraFields, $formItem);

        if (!empty($extraFields)) {
            foreach ($extraFields as $extraField) {
                $formInstance->addField($extraField);
            }
        }
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
        $suffix = (null !== $key) ? '.'.$key.'.trad' : '.trad';
        $translation = __($fiedName.$suffix, WWP_CONTACT_TEXTDOMAIN);

        // Hierarchie
        $translationWithId = __($fiedName.'.'.$formId.$suffix, WWP_CONTACT_TEXTDOMAIN);

        if ($fiedName.'.'.$formId.$suffix != $translationWithId) {
            $translation = $translationWithId;
        } elseif ($fiedName.$suffix!= $translation){
            //$translation = $translation;
        }
        elseif (false === $required) {
            $translation = false;
        } elseif (true === $required && true === $strict) {
            $translation = $translationWithId;
        }

        // Result
        return $translation;
    }
}
