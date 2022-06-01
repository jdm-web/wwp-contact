<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;
use WonderWp\Plugin\Core\Framework\PageSettings\AbstractPageSettingsService;

class ContactPageSettingsService extends AbstractPageSettingsService
{
    public static $contact_select_field_name = 'form';
    public function getSettingsFields()
    {
        $fields = [];
        $fields[] = self::getContactSelectField();

        return $fields;
    }

    /**
     * get the SelectField containing all contributed Contact Forms
     * @param array $metas
     * @return SelectField
     */
    public function getContactSelectField($metas = []) {
        $selectedForm = !empty($metas[self::$contact_select_field_name]) ? reset($metas[self::$contact_select_field_name]) : null;

        $formSelect = new SelectField(self::$contact_select_field_name, $selectedForm, [
            'label' => 'Formulaire(s) à brancher',
            'help'=>"Si vous en choisissez plusieurs, un sélecteur sera affiché en front",
            'inputAttributes'=>['multiple'=>true]
        ]);
        /** @var ContactFormRepository $repository */
        $repository = $this->manager->getService('contactFormRepository');
        $forms      = $repository->findAll();
        $opts       = [
            '' => 'Choisissez le(s) formulaire(s) à afficher',
        ];
        if (!empty($forms)) {
            foreach ($forms as $f) {
                /** @var ContactFormEntity $f */
                $opts[$f->getId()] = stripslashes($f->getName());
            }
        }
        $formSelect->setOptions($opts);
        return $formSelect;
    }

}
