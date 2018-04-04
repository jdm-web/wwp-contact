<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 13/09/2016
 * Time: 16:37
 */

namespace WonderWp\Plugin\Contact\Service;

use Doctrine\ORM\EntityManager;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
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
     * @return SelectField
     */
    public static function getContactSelectField($metas = []) {
        $selectedForm = !empty($metas[self::$contact_select_field_name]) ? reset($metas[self::$contact_select_field_name]) : null;

        $formSelect = new SelectField(self::$contact_select_field_name, $selectedForm, ['label' => 'Formulaire à brancher']);
        $container  = Container::getInstance();
        /** @var EntityManager $em */
        $em         = $container->offsetGet('entityManager');
        $repository = $em->getRepository(ContactFormEntity::class);
        $forms      = $repository->findAll();
        $opts       = [
            '' => 'Choisissez le formulaire à afficher',
        ];
        if (!empty($forms)) {
            foreach ($forms as $f) {
                /** @var ContactFormEntity $f */
                $opts[$f->getId()] = $f->getName();
            }
        }
        $formSelect->setOptions($opts);
        return $formSelect;
    }

}
