<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 06/06/2017
 * Time: 09:47
 */

namespace WonderWp\Plugin\Contact\Service;

use Doctrine\ORM\EntityManager;
use Respect\Validation\Validator;
use function WonderWp\Framework\array_merge_recursive_distinct;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Form\Field\AbstractField;
use WonderWp\Framework\Form\Field\HiddenField;
use WonderWp\Framework\Form\Field\NonceField;
use WonderWp\Framework\Form\Field\SelectField;
use WonderWp\Framework\Form\Form;
use WonderWp\Framework\Form\FormInterface;
use WonderWp\Framework\Form\FormViewInterface;
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

        // Add other necessary field
        $f = new HiddenField('form', $formItem->getId());
        $formInstance->addField($f);

        $nonce = new NonceField('nonce');
        $formInstance->addField($nonce);

        if ($post) {
            $f = new HiddenField('post', $post->ID);
            $formInstance->addField($f);
        }

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

        $label           = __($field->getName() . '.trad', WWP_CONTACT_TEXTDOMAIN);
        $placeHolder     = __($field->getName() . '.placeholder.trad', WWP_CONTACT_TEXTDOMAIN);
        
        $displayRules    = [
            'label' => $label,
        ];

        if($placeHolder!=$field->getName() . '.placeholder.trad'){
            $displayRules['inputAttributes']=['placeholder'=>$placeHolder];
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
