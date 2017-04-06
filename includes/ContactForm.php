<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 09/08/2016
 * Time: 17:16
 */

namespace WonderWp\Plugin\Contact;




use WonderWp\Framework\Form\Field\HiddenField;
use WonderWp\Plugin\Core\Framework\Entity\EntityAttribute;
use WonderWp\Plugin\Core\Framework\Entity\EntityRelation;
use WonderWp\Plugin\Core\Framework\Form\ModelForm;

/**
 * Class ContactForm
 * @package WonderWp\Plugin\Contact
 * Class that defines the form to use when adding / editing the entity
 */
class ContactForm extends ModelForm{

    public function newField(EntityAttribute $attr)
    {
        $fieldName = $attr->getFieldName();
        $entity = $this->getModelInstance();

        //Add here particular cases for your different fields
        switch($fieldName){
            default:
                $f = parent::newField($attr);
                break;
        }
        return $f;
    }

    public function newRelation(EntityRelation $relationAttr)
    {
        $fieldName = $relationAttr->getFieldName();
        $entity = $this->getModelInstance();
        $val = $entity->$fieldName;

        //Add here particular cases for your different fields
        switch($fieldName){
            case'form':
                $f = new HiddenField($fieldName,$val);
                break;
            default:
                $f = parent::newRelation($relationAttr);
                break;
        }
        return $f;
    }

}
