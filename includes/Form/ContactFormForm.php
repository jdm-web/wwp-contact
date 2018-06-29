<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 09/08/2016
 * Time: 17:16
 */

namespace WonderWp\Plugin\Contact\Form;

use Doctrine\ORM\EntityManager;
use function GuzzleHttp\default_ca_bundle;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Form\Field\BooleanField;
use WonderWp\Framework\Form\Field\FieldGroup;
use WonderWp\Framework\Form\Field\InputField;
use WonderWp\Framework\Form\FormInterface;
use WonderWp\Framework\Form\FormValidatorInterface;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\EntityMapping\EntityAttribute;
use WonderWp\Plugin\Core\Framework\Form\ModelForm;

/**
 * Class ContactForm
 * @package WonderWp\Plugin\Contact
 * Class that defines the form to use when adding / editing the entity
 */
class ContactFormForm extends ModelForm
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
        $fieldName = $attr->getFieldName();
        $entity    = $this->getModelInstance();
        $val       = stripslashes($entity->$fieldName);
        $label     = __($fieldName . '.trad', $this->textDomain);

        switch ($fieldName) {
            case 'data':
                $field       = $this->getModelInstance()->$fieldName;
                $savedFields = json_decode($field, true);

                if (!is_array($savedFields)) {
                    $savedFields = [];
                }

                $f = $this->_generateFormBuilder($fieldName, $savedFields);
                break;
            case'sendTo':
                $f = new InputField($fieldName, $val, ['label' => $label, 'help' => 'Vous pouvez utiliser plusieurs adresses mail en les séparant par des '.ContactManager::multipleAddressSeparator]);
                break;
            case'cc':
                $f = new InputField($fieldName, $val, ['label' => $label, 'help' => 'Vous pouvez utiliser plusieurs adresses mail en les séparant par des '.ContactManager::multipleAddressSeparator]);
                break;
            case'numberOfDaysBeforeRemove':
                $f = parent::newField($attr);
                if(empty($val)){
                    $f->setValue((int)0);
                }
                break;
            default:
                $f = parent::newField($attr);
                break;
        }

        return $f;
    }

    /**
     * @param string $name
     * @param array  $savedFields
     *
     * @return FieldGroup
     */
    private function _generateFormBuilder($name, array $savedFields = [])
    {
        $fieldGroup = new FieldGroup($name, null, ['label' => 'Champs du formulaire : ']);

        /**
         * @var EntityManager            $em
         * @var ContactFormFieldEntity[] $fields
         */
        $container       = Container::getInstance();
        $em              = $container->offsetGet('entityManager');
        $fieldRepository = $em->getRepository(ContactFormFieldEntity::class);
        $fields          = $fieldRepository->findAll();

        foreach ($savedFields as $fieldId => $fieldData) {
            $field = $fieldRepository->find($fieldId);

            if (!$field instanceof ContactFormFieldEntity) {
                continue;
            }

            $fieldGroup->addFieldToGroup($this->_generateFieldGroup($field, $fieldData));
        }

        foreach ($fields as $field) {
            if (array_key_exists($field->getId(), $savedFields)) {
                continue;
            }

            $fieldGroup->addFieldToGroup($this->_generateFieldGroup($field, []));
        }

        return $fieldGroup;
    }

    /**
     * @param ContactFormFieldEntity $field
     * @param array                  $options
     *
     * @return FieldGroup
     */
    private function _generateFieldGroup(ContactFormFieldEntity $field, array $options)
    {
        // Field name
        $displayRules = [
            'label'           => __($field->getName() . '.trad', WWP_CONTACT_TEXTDOMAIN),
            'labelAttributes' => [
                'class' => ['dragHandle'],
            ],
            'inputAttributes' => [
                'name' => 'data[' . $field->getId() . ']',
            ],
        ];
        $fieldGroup   = new FieldGroup('data_' . $field->getId() . '', null, $displayRules);

        // Field enabled ?
        $displayRules      = [
            'label'           => __('Enabled', WWP_CONTACT_TEXTDOMAIN),
            'inputAttributes' => [
                'name' => "data[{$field->getId()}][enabled]",
            ],
        ];
        $enabledFieldGroup = new BooleanField($field->getId() . '_enabled', $field->isEnabled($options), $displayRules);
        $fieldGroup->addFieldToGroup($enabledFieldGroup);

        // Field required ?
        $displayRules       = [
            'label'           => __('Required', WWP_CONTACT_TEXTDOMAIN),
            'inputAttributes' => [
                'name' => "data[{$field->getId()}][required]",
            ],
        ];
        $requiredFieldGroup = new BooleanField($field->getId() . '_required', $field->isRequired($options), $displayRules);
        $fieldGroup->addFieldToGroup($requiredFieldGroup);

        return $fieldGroup;
    }

    /** @inheritdoc */
    public function handleRequest(array $data, FormValidatorInterface $formValidator)
    {
        if (array_key_exists('data', $data) && is_array($data['data'])) {
            foreach ($data['data'] as $fieldName => &$field) {
                if (array_key_exists('choices', $field) && array_key_exists('_new', $field['choices'])) {
                    unset($field['choices']['_new']);
                }
            }

            $data['data'] = json_encode($data['data']);
        }

        $errors = parent::handleRequest($data, $formValidator);

        $this->buildForm();

        return $errors;
    }
}
