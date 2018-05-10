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
        $formInstance = Container::getInstance()->offsetGet('wwp.forms.form');

        // Add configured fields
        $data = json_decode($formItem->getData(), true);

        if (!empty($data)) {
            foreach ($data as $fieldId => $fieldOptions) {
                $field = $this->generateDefaultField($fieldId, $fieldOptions);
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
    private function generateDefaultField($fieldId, $fieldOptions)
    {
        /** @var EntityManager $em */
        $em    = Container::getInstance()->offsetGet('entityManager');
        $field = $em->getRepository(ContactFormFieldEntity::class)->find($fieldId);

        if (!$field instanceof ContactFormFieldEntity) {
            return null;
        }

        $label       = __($field->getName() . '.trad', WWP_CONTACT_TEXTDOMAIN);
        $placeHolder = __($field->getName() . '.placeholder.trad', WWP_CONTACT_TEXTDOMAIN);

        $displayRules = [
            'label' => $label,
        ];

        if ($placeHolder != $field->getName() . '.placeholder.trad') {
            $displayRules['inputAttributes'] = ['placeholder' => $placeHolder];
        }

        $validationRules = [];

        if ($field->isRequired($fieldOptions)) {
            $validationRules[] = Validator::notEmpty();
        }

        $fieldClass    = str_replace('\\\\', '\\', $field->getType());
        $fieldInstance = new $fieldClass($field->getName(), null, $displayRules, $validationRules);

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

    protected function addOtherNecessaryFields(ContactFormEntity $formItem, FormInterface $formInstance, \WP_Post $post = null)
    {
        // Add other necessary fields

        $extraFields = [
            'form'     => new HiddenField('form', $formItem->getId()),
            'nonce'    => new NonceField('nonce'),
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
}
