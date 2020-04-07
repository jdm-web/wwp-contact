<?php

namespace WonderWp\Plugin\Contact\Form;

use WonderWp\Component\Form\Field\BooleanField;
use WonderWp\Component\Form\Field\BtnField;
use WonderWp\Component\Form\Field\FieldGroup;
use WonderWp\Component\Form\Field\InputField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\Form\FormValidatorInterface;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;
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
        $formInstance->setName('contact-form-form');

        return parent::setFormInstance($formInstance);
    }

    public function buildForm(){
        $this->buildDataFields();
        parent::buildForm();
    }

    public function addGroupButton(){
        $addBtn = new BtnField('add-group', null, ['label' => 'Ajouter un groupe', 'inputAttributes' => ['class' => ['add-repeatable'], 'data-repeatable' => '_newgroup_']]);
        $this->addField($addBtn);
    }

    public function buildDataFields(){


        $fieldName = "data";

        $field       = $this->getModelInstance()->$fieldName;
        $savedFields = json_decode($field, true);

        if (!is_array($savedFields)) {
            $savedFields = [];
        }

        $treatedFields = [];
        if( isset($savedFields["groups"]) ){
            foreach($savedFields["groups"] as $id_group => $group){
                $listFields = [];
                $label = $group["label"];
                $group_name = "g".$id_group;
                foreach ($savedFields["fields"] as $id_field => $field){
                    if((int)$field["group"] == $id_group){
                        $listFields[$id_field] = $field;
                        $treatedFields[$id_field] = true;
                    }
                }

                $f = $this->_generateFormBuilder($group_name, $listFields, $label, $id_group, true);
                $this->addField($f);
            }
        }
        else{
            $treatedFields = $savedFields;
            if(count($savedFields) > 0) {
                $f = $this->_generateFormBuilder("g1", $savedFields, 'Champs du formulaire : ', 1, true );
                $this->addField($f);
            }
            else{//si on n'a aucun groupe pour le moment on en crée un vide
                $f = $this->_generateFormBuilder("g1", [], 'Groupe par défaut: ', 1, true );
                $this->addField($f);
            }
        }



        $f = $this->_generateFormBuilder('g_newgroup_', [], 'NewGroup', '_newgroup_', true, true);
        $this->addField($f);
        $this->addGroupButton();

        $em              = EntityManager::getInstance();
        $fieldRepository = $em->getRepository(ContactFormFieldEntity::class);
        $fields          = $fieldRepository->findAll();

        foreach ($fields as $field) {
            if (!array_key_exists($field->getId(), $treatedFields)) {
                $otherFields[$field->getId()] = ["enabled" => 0, "required" => 0];
            }
        }
        $f = $this->_generateFormBuilder("Others", $otherFields, 'Champs disponibles : ');
        $this->addField($f);

    }

    /** @inheritdoc */
    public function newField(EntityAttribute $attr)
    {
        $fieldName = $attr->getFieldName();
        $entity    = $this->getModelInstance();
        $val       = stripslashes($entity->$fieldName);
        $label     = __($fieldName . '.trad', $this->textDomain);

        switch ($fieldName) {
            case 'data': //not treated here as it can generate several FormGroups
                $f= null;
                break;
            case'sendTo':
                $f = new InputField($fieldName, $val, [
                    'label' => $label,
                    'help'  => 'Vous pouvez utiliser plusieurs adresses mail en les séparant par des ' . ContactManager::multipleAddressSeparator,
                ]);
                break;
            case'cc':
                $f = new InputField($fieldName, $val, [
                    'label' => $label,
                    'help'  => 'Vous pouvez utiliser plusieurs adresses mail en les séparant par des ' . ContactManager::multipleAddressSeparator,
                ]);
                break;
            case'numberOfDaysBeforeRemove':
                $f = parent::newField($attr);
                if (empty($val)) {
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
    private function _generateFormBuilder($name, array $savedFields = [], $label_group = '', $id_group = 0, $editable = false, $hidden = false)
    {
        $displayRules['label'] = $label_group;
        $displayRules['inputAttributes']['class'] = ['form-group-wrap', 'repeatable'];
        $displayRules['wrapAttributes']['class'] = ['group-wrap'];
        if($hidden){
            $displayRules['wrapAttributes']['class'][] = 'hidden';
        }
        $displayRules['wrapAttributes']['id'] = ['group_wrap_'.$id_group];

        $fieldGroup = new FieldGroup($name, null, $displayRules);

        /**
         * @var EntityManager            $em
         * @var ContactFormFieldEntity[] $fields
         */
        $em              = EntityManager::getInstance();
        $fieldRepository = $em->getRepository(ContactFormFieldEntity::class);


        if($editable) {
            $groupNameField = new InputField("group_" . $id_group, $label_group, []);
            $fieldGroup->addFieldToGroup($groupNameField);
        }

        foreach ($savedFields as $fieldId => $fieldData) {
            $field = $fieldRepository->find($fieldId);

            if (!$field instanceof ContactFormFieldEntity) {
                continue;
            }
            $fieldGroup->addFieldToGroup($this->_generateFieldGroup($field, $fieldData, $name));
        }

        return $fieldGroup;
    }

    /**
     * @param ContactFormFieldEntity $field
     * @param array                  $options
     *
     * @return FieldGroup
     */
    private function _generateFieldGroup(ContactFormFieldEntity $field, array $options, $group_name)
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
        $fieldGroup   = new FieldGroup('data_'.$group_name.'_' . $field->getId() . '', null, $displayRules);

        // Field enabled ?
        $displayRules      = [
            'label'           => __('Enabled', WWP_CONTACT_TEXTDOMAIN),
            'inputAttributes' => [
                'name' => "data_".$group_name."[{$field->getId()}][enabled]",
            ],
        ];
        $enabledFieldGroup = new BooleanField($field->getId() . '_enabled', $field->isEnabled($options), $displayRules);
        $fieldGroup->addFieldToGroup($enabledFieldGroup);

        // Field required ?
        $displayRules       = [
            'label'           => __('Required', WWP_CONTACT_TEXTDOMAIN),
            'inputAttributes' => [
                'name' => "data_".$group_name."[{$field->getId()}][required]",
            ],
        ];
        $requiredFieldGroup = new BooleanField($field->getId() . '_required', $field->isRequired($options), $displayRules);
        $fieldGroup->addFieldToGroup($requiredFieldGroup);

        return $fieldGroup;
    }

    /** @inheritdoc */
    public function handleRequest(array $data, FormValidatorInterface $formValidator, array $formData = [])
    {
        //manage data
        $dataFields = [];
        $dataGroups = [];
        $data_prefix = 'data_g';
        $group_prefix = 'group_';
        foreach ($data as $key => $val){
            $pos = strpos($key, $data_prefix);
            $idGroupField = substr($key, strlen($data_prefix), strlen($key));

            if($pos !== false){
                foreach($val as $id_field => $dataGroup){
                    $dataGroup["group"] = $idGroupField;
                    $dataFields[$id_field] = $dataGroup;
                }
            }

            $posG = strpos($key, $group_prefix);
            $idGroup = substr($key, strlen($group_prefix), strlen($key));
            if($posG !== false && $idGroup != "_newgroup_"){
                $dataGroups[$idGroup] = ["enabled" => "1", "label" => $val];
            }
        }

        //data des groupes
        $data["data"] = [
            "fields" => $dataFields,
            "groups" => $dataGroups
        ];

        $data["data"] = json_encode($data["data"]);

        if (!isset($data['saveMsg'])) {
            $data['saveMsg'] = 0;
        }

        if (!isset($data['bystep'])) {
            $data['bystep'] = 0;
        }

        $errors = parent::handleRequest($data, $formValidator, $formData);

        $this->buildForm();

        return $errors;
    }
}
