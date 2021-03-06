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
use WonderWp\Component\Form\Field\UrlField;
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

            //SelectField : choices options
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

            //FileField : Extensions options
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

            //TextArea : Max length option
            if ($contactFormFieldType === TextAreaField::class) {
                $val          = $contactFormField->getOption('maxlength', 500);
                $extFieldName = 'options[maxlength]';
                $f            = new InputField('options-maxlength', $val, [
                    'label'           => trad('maxlength.trad', WWP_CONTACT_TEXTDOMAIN),
                    'inputAttributes' => ['name' => $extFieldName],
                ]);
                $optionsField->addFieldToGroup($f);
            }

            //Any type : autocomplete option
            $autocompleteVal       = $contactFormField->getOption('autocomplete', '');
            $autoCompleteFieldName = 'options[autocomplete]';
            $f                     = new InputField('options-autocomplete', $autocompleteVal, [
                'label'           => trad('autocomplete.trad', WWP_CONTACT_TEXTDOMAIN),
                'inputAttributes' => ['name' => $autoCompleteFieldName],
            ]);
            $optionsField->addFieldToGroup($f);

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
            'label'           => $id !== '_new' ? 'Choix ' . $id : '',
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
                'name'  => "{$fieldName}[{$id}][locale]",
                'class' => $id === '_new' ? ['no-chosen'] : [],
            ],
        ];
        $localeField  = LocaleField::getInstance("subject_{$id}_locale", array_key_exists('locale', $choice) ? $choice['locale'] : null, $displayRules);
        $fieldGroup->addFieldToGroup($localeField);

        // Label
        $displayRules = [
            'inputAttributes' => [
                'placeholder' => __('Label', WWP_CONTACT_TEXTDOMAIN),
                'name'        => "{$fieldName}[{$id}][label]",
            ],
            'label'           => "Libellé texte de l'option",
        ];
        $labelField   = new InputField("choice_{$id}_label", array_key_exists('label', $choice) ? stripslashes($choice['label']) : null, $displayRules);
        $fieldGroup->addFieldToGroup($labelField);

        // Value
        $displayRules = [
            'inputAttributes' => [
                'placeholder' => __('Value', WWP_CONTACT_TEXTDOMAIN),
                'name'        => "{$fieldName}[{$id}][value]",
            ],
            'label'           => "Valeur de l'option",
        ];
        $valueField   = new InputField("choice_{$id}_value", array_key_exists('value', $choice) ? stripslashes($choice['value']) : null, $displayRules);
        $fieldGroup->addFieldToGroup($valueField);

        // Dest
        $displayRules = [
            'inputAttributes' => [
                'placeholder' => __('Dest', WWP_CONTACT_TEXTDOMAIN),
                'name'        => "{$fieldName}[{$id}][dest]",
            ],
            'label'           => "Destinataire particulier",
            'help'            => "Mettez ici si besoin une adresse mail vers laquelle le message partira (seulement si c'est une adresse différente du formulaire initial)",
        ];
        $destField    = new EmailField("choice_{$id}_dest", array_key_exists('dest', $choice) ? stripslashes($choice['dest']) : null, $displayRules);
        $fieldGroup->addFieldToGroup($destField);

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
    public function handleRequest(array $data, FormValidatorInterface $formValidator, array $formData = [])
    {
        if (!empty($data['name'])) {
            $data['name'] = sanitize_title($data['name']);
        }

        if (array_key_exists('options', $data) && is_array($data['options'])) {
            if (array_key_exists('choices', $data['options']) && is_array($data['options']['choices']) && array_key_exists('_new', $data['options']['choices'])) {
                unset($data['options']['choices']['_new']);
            }
        }

        $errors = parent::handleRequest($data, $formValidator, $formData);

        //Fix fill issue with type
        $this->buildForm();

        return $errors;
    }

    /**
     * @return array
     */
    protected function getAvailableTypes()
    {
        $availableFieldTypes = [
            InputField::class    => trad('textfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            EmailField::class    => trad('emailfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            TextAreaField::class => trad('textareafieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            SelectField::class   => trad('selectfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            FileField::class     => trad('filefieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            CheckBoxField::class => trad('checkboxfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            HiddenField::class   => trad('hiddenfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            NumericField::class  => trad('numericfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            PhoneField::class    => trad('phonefieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
            UrlField::class      => trad('urlfieldtype.trad', WWP_CONTACT_TEXTDOMAIN),
        ];

        return apply_filters('contact.available_field_types', $availableFieldTypes);
    }

}
