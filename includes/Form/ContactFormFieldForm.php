<?php
namespace WonderWp\Plugin\Contact\Form;

use WonderWp\Framework\Form\Field\BtnField;
use WonderWp\Framework\Form\Field\CheckBoxField;
use WonderWp\Framework\Form\Field\EmailField;
use WonderWp\Framework\Form\Field\FieldGroup;
use WonderWp\Framework\Form\Field\FileField;
use WonderWp\Framework\Form\Field\InputField;
use WonderWp\Framework\Form\Field\SelectField;
use WonderWp\Framework\Form\Field\TextAreaField;
use WonderWp\Framework\Form\FormInterface;
use WonderWp\Framework\Form\FormValidatorInterface;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\EntityMapping\EntityAttribute;
use WonderWp\Plugin\Core\Framework\Form\ModelForm;
use WonderWp\Plugin\Translator\Form\Field\LocaleField;

class ContactFormFieldForm extends ModelForm
{
    /** @inheritdoc */
    public function setFormInstance(FormInterface $formInstance)
    {
        $formInstance->setName('contact-form');

        return parent::setFormInstance($formInstance);
    }

    /** @inheritdoc */
    public function newField(EntityAttribute $attr)
    {
        /** @var ContactFormFieldEntity $contactFormField */
        $contactFormField     = $this->getModelInstance();
        $contactFormFieldType = str_replace('\\\\', '\\', $contactFormField->getType());
        $fieldName            = $attr->getFieldName();
        if ($fieldName === 'type') {
            $field = new SelectField('type', $contactFormFieldType, [
                'label' => __('type.trad', WWP_CONTACT_TEXTDOMAIN),
            ]);

            $field->setOptions($this->getAvailableTypes());

            return $field;
        };

        if ($fieldName === 'options') {
            $optionsField = new FieldGroup('options');

            if ($contactFormFieldType === SelectField::class) {
                $choicesFieldName = 'options[choices]';
                $choices          = new FieldGroup('options-choices', null, [
                    'label' => __('choices.trad', WWP_CONTACT_TEXTDOMAIN),
                ]);

                foreach ($contactFormField->getOption('choices', []) as $id => $choice) {
                    $choices->addFieldToGroup($this->_generateSelectFieldChoice($choicesFieldName, $choice, $id));
                }

                $choices->addFieldToGroup($this->_generateSelectFieldChoice($choicesFieldName));

                $addBtn = new BtnField('add-choice', null, ['label' => __('choice_add.trad', WWP_CONTACT_TEXTDOMAIN)]);
                $choices->addFieldToGroup($addBtn);

                $optionsField->addFieldToGroup($choices);
            }

            return $optionsField;
        }

        return parent::newField($attr);
    }

    /**
     * @param string $fieldName
     * @param array  $choice
     * @param string $id
     *
     * @return FieldGroup
     */
    private function _generateSelectFieldChoice($fieldName, array $choice = [], $id = '_new')
    {
        $displayRules = [
            'wrapAttributes'  => [
                'no-wrap' => true,
            ],
            'inputAttributes' => [
                'class' => $id === '_new' ? ['new-choice', 'hidden'] : ['choice'],
            ],
        ];

        // Choice group
        $fieldGroup = new FieldGroup("choice_{$id}", null, $displayRules);

        // Locale
        $displayRules = [
            'label'           => __('Choice', WWP_CONTACT_TEXTDOMAIN) . ' ' . $id,
            'labelAttributes' => [
                'class' => ['dragHandle'],
            ],
            'inputAttributes' => [
                'name' => "{$fieldName}[{$id}][locale]",
            ],
        ];
        $localeField  = LocaleField::getInstance("subject_{$id}_locale", array_key_exists('locale', $choice) ? $choice['locale'] : null, $displayRules);
        $fieldGroup->addFieldToGroup($localeField);

        // Text
        $displayRules = [
            'inputAttributes' => [
                'placeholder' => __('Label', WWP_CONTACT_TEXTDOMAIN),
                'name'        => "{$fieldName}[{$id}][label]",
            ],
        ];
        $labelField   = new InputField("choice_{$id}_label", array_key_exists('label', $choice) ? $choice['label'] : null, $displayRules);
        $fieldGroup->addFieldToGroup($labelField);

        // Value
        $displayRules = [
            'inputAttributes' => [
                'placeholder' => __('Value', WWP_CONTACT_TEXTDOMAIN),
                'name'        => "{$fieldName}[{$id}][value]",
            ],
        ];
        $valueField   = new InputField("choice_{$id}_value", array_key_exists('value', $choice) ? $choice['value'] : null, $displayRules);
        $fieldGroup->addFieldToGroup($valueField);

        // Remove button
        $removeBtn = new BtnField("remove-choice-{$id}", null, [
            'label'           => '&times;',
            'inputAttributes' => [
                'class' => ['button', 'remove-choice'],
            ],
        ]);

        $fieldGroup->addFieldToGroup($removeBtn);

        return $fieldGroup;
    }

    /** @inheritdoc */
    public function handleRequest(array $data, FormValidatorInterface $formValidator)
    {
        if (array_key_exists('options', $data) && is_array($data['options'])) {
            if (array_key_exists('choices', $data['options']) && is_array($data['options']['choices']) && array_key_exists('_new', $data['options']['choices'])) {
                unset($data['options']['choices']['_new']);
            }
        }

        $errors = parent::handleRequest($data, $formValidator);

        return $errors;
    }

    /**
     * @return array
     */
    protected function getAvailableTypes()
    {
        return [
            InputField::class    => __('text.trad', WWP_CONTACT_TEXTDOMAIN),
            EmailField::class    => __('email.trad', WWP_CONTACT_TEXTDOMAIN),
            TextAreaField::class => __('textarea.trad', WWP_CONTACT_TEXTDOMAIN),
            SelectField::class   => __('select.trad', WWP_CONTACT_TEXTDOMAIN),
            FileField::class     => __('file.trad', WWP_CONTACT_TEXTDOMAIN),
            CheckBoxField::class => __('checkbox.trad', WWP_CONTACT_TEXTDOMAIN)
        ];
    }

}
