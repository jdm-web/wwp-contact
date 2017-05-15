<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 09/08/2016
 * Time: 17:16
 */

namespace WonderWp\Plugin\Contact\Form;

use WonderWp\Framework\Form\Field\FieldGroup;
use WonderWp\Framework\Form\Field\HiddenField;
use WonderWp\Framework\Form\Field\InputField;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;
use WonderWp\Plugin\Core\Framework\EntityMapping\EntityAttribute;
use WonderWp\Plugin\Core\Framework\EntityMapping\EntityRelation;
use WonderWp\Plugin\Core\Framework\Form\ModelForm;

/**
 * Class ContactForm
 * @package WonderWp\Plugin\Contact
 * Class that defines the form to use when adding / editing the entity
 */
class ContactForm extends ModelForm
{

    public function newField(EntityAttribute $attr)
    {
        $fieldName = $attr->getFieldName();
        $entity    = $this->getModelInstance();

        //Add here particular cases for your different fields
        switch ($fieldName) {
            case'data':
                $f = $this->generateDataGroup($attr);
                break;
            default:
                $f = parent::newField($attr);
                break;
        }

        return $f;
    }

    public function newRelation(EntityRelation $relationAttr)
    {
        $fieldName = $relationAttr->getFieldName();
        $entity    = $this->getModelInstance();
        $val       = $entity->$fieldName;

        //Add here particular cases for your different fields
        switch ($fieldName) {
            case'form':
                $f = new HiddenField($fieldName, $val);
                break;
            default:
                $f = parent::newRelation($relationAttr);
                break;
        }

        return $f;
    }

    public function generateDataGroup(EntityAttribute $attr)
    {
        $fieldName = $attr->getFieldName();
        /** @var ContactEntity $entity */
        $entity   = $this->getModelInstance();
        $formItem = $entity->getForm();
        $data     = json_decode($formItem->getData(), true);

        $g = new FieldGroup($fieldName);

        if (!empty($data)) {
            $em        = EntityManager::getInstance();
            $fieldRepo = $em->getRepository(ContactFormFieldEntity::class);
            foreach ($data as $fieldId => $fieldOptions) {
                $field = $fieldRepo->find($fieldId);
                if ($field instanceof ContactFormFieldEntity) {
                    $f = new InputField($field->getName(),$entity->getData($field->getName()),['label'=>__($field->getName().'.trad',$this->getTextDomain())]);
                    $g->addFieldToGroup($f);
                }
            }
        }

        return $g;
    }

}
