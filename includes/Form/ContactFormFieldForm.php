<?php

namespace WonderWp\Plugin\Contact\Form;

use WonderWp\Component\Form\Field\BtnField;
use WonderWp\Component\Form\Field\CheckBoxField;
use WonderWp\Component\Form\Field\EmailField;
use WonderWp\Component\Form\Field\FieldGroup;
use WonderWp\Component\Form\Field\FileField;
use WonderWp\Component\Form\Field\HiddenField;
use WonderWp\Component\Form\Field\InputField;
use WonderWp\Component\Form\Field\NumericField;
use WonderWp\Component\Form\Field\PhoneField;
use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Component\Form\Field\TextAreaField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\Form\FormValidatorInterface;
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

        $fieldName = $attr->getFieldName();
        if ($fieldName === 'type') {
            $displayRules = [
                'label' => __('type.trad', WWP_CONTACT_TEXTDOMAIN),
            ];

            if ($contactFormField->getId() > 0) {
                $displayRules['help'] = '<br /><h3>Administration des traductions</h3>
            
                <h4>Pour administrer les clés de ce champ de manière globale</h4>
                <ul>
                    <li>Pour traduire le <strong>label</strong> du champ : utiliser <strong>' . $contactFormField->getName() . '.trad</strong></li>
                    <li>Pour traduire le <strong>placeholder</strong> du champ : utiliser <strong>' . $contactFormField->getName() . '.placeholder.trad</strong></li>
                    <li>Pour traduire le <strong>texte d\'info</strong> du champ (ex rgpd) : utiliser <strong>' . $contactFormField->getName() . '.help.trad</strong></li>
                </ul>
                <h4>Pour administrer les clés de ce champ pour un formulaire en particulier</h4>
                <ul>
                    <li>Pour traduire le <strong>label</strong> du champ : utiliser <strong>' . $contactFormField->getName() . '.id_du_form.trad</strong> (ex ' . $contactFormField->getName() . '.1.trad)</li>
                    <li>Pour traduire le <strong>placeholder</strong> du champ : utiliser <strong>' . $contactFormField->getName() . '.id_du_form.placeholder.trad</strong> (ex ' . $contactFormField->getName() . '.1.placeholder.trad)</li>
                    <li>Pour traduire le <strong>texte d\'info</strong> du champ (ex rgpd) : utiliser <strong>' . $contactFormField->getName() . '.id_du_form.help.trad</strong> (ex ' . $contactFormField->getName() . '.1.help.trad)</li>
                </ul>                
                ';
            }

            $field = new SelectField('type', $contactFormFieldType, $displayRules);

            $field->setOptions($this->getAvailableTypes());

            return $field;
        };

        if ($fieldName === 'options') {
            $optionsField = new FieldGroup('options');

            if ($contactFormFieldType === SelectField::class) {
                $choicesFieldName = 'options[choices]';
                $choices          = new FieldGroup('options-choices', null, [
                    'label'           => __('choices.trad', WWP_CONTACT_TEXTDOMAIN),
                    'inputAttributes' => ['name' => $choicesFieldName],
                ]);

                foreach ($contactFormField->getOption('choices', []) as $id => $choice) {
                    $choices->addFieldToGroup($this->_generateSelectFieldChoice($choicesFieldName, $choice, $id));
                }

                $choices->addFieldToGroup($this->_generateSelectFieldChoice($choicesFieldName));

                $addBtn = new BtnField('add-choice', null, ['label' => __('choice_add.trad', WWP_CONTACT_TEXTDOMAIN)]);
                $choices->addFieldToGroup($addBtn);

                $optionsField->addFieldToGroup($choices);
            }

            if ($contactFormFieldType === FileField::class) {
                $val          = $contactFormField->getOption('extensions', 'pdf,doc,docx,odt,jpg,jpeg,png');
                $extFieldName = 'options[extensions]';
                $f            = new InputField('options-extensions', $val, [
                    'label'           => trad('allowed.extensions.trad', WWP_CONTACT_TEXTDOMAIN),
                    'inputAttributes' => ['name' => $extFieldName],
                    'help'            => trad('allowed.extensions.help', WWP_CONTACT_TEXTDOMAIN),
                ]);
                $optionsField->addFieldToGroup($f);
            }

            if ($contactFormFieldType === TextAreaField::class) {
                $val          = $contactFormField->getOption('maxlength', 500);
                $extFieldName = 'options[maxlength]';
                $f            = new InputField('options-maxlength', $val, [
                    'label'           => trad('maxlength.trad', WWP_CONTACT_TEXTDOMAIN),
                    'inputAttributes' => ['name' => $extFieldName],
                ]);
                $optionsField->addFieldToGroup($f);
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
        $labelField   = new InputField("choice_{$id}_label", array_key_exists('label', $choice) ? stripslashes($choice['label']) : null, $displayRules);
        $fieldGroup->addFieldToGroup($labelField);

        // Value
        $displayRules = [
            'inputAttributes' => [
                'placeholder' => __('Value', WWP_CONTACT_TEXTDOMAIN),
                'name'        => "{$fieldName}[{$id}][value]",
            ],
        ];
        $valueField   = new InputField("choice_{$id}_value", array_key_exists('value', $choice) ? stripslashes($choice['value']) : null, $displayRules);
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
        if (!empty($data['name'])) {
            $data['name'] = sanitize_title($data['name']);
        }

        if (array_key_exists('options', $data) && is_array($data['options'])) {
            if (array_key_exists('choices', $data['options']) && is_array($data['options']['choices']) && array_key_exists('_new', $data['options']['choices'])) {
                unset($data['options']['choices']['_new']);
            }
        }

        $errors = parent::handleRequest($data, $formValidator);

        //Fix fill issue with type
        $this->buildForm();

        return $errors;
    }

    /**
     * @return array
     */
    protected function getAvailableTypes()
    {
        return [
            InputField::class    => trad('text.trad', WWP_CONTACT_TEXTDOMAIN),
            EmailField::class    => trad('email.trad', WWP_CONTACT_TEXTDOMAIN),
            TextAreaField::class => trad('textarea.trad', WWP_CONTACT_TEXTDOMAIN),
            SelectField::class   => trad('select.trad', WWP_CONTACT_TEXTDOMAIN),
            FileField::class     => trad('file.trad', WWP_CONTACT_TEXTDOMAIN),
            CheckBoxField::class => trad('checkbox.trad', WWP_CONTACT_TEXTDOMAIN),
            HiddenField::class   => trad('hidden.trad', WWP_CONTACT_TEXTDOMAIN),
            NumericField::class  => trad('numeric.trad', WWP_CONTACT_TEXTDOMAIN),
            PhoneField::class    => trad('phone.trad', WWP_CONTACT_TEXTDOMAIN),
        ];
    }

}
