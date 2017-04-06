<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 13/09/2016
 * Time: 16:37
 */

namespace WonderWp\Plugin\Contact;


use Doctrine\ORM\EntityManager;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Form\Field\SelectField;
use WonderWp\Plugin\Core\Framework\PageSettings\AbstractPageSettingsService;

class ContactPageSettingsService extends AbstractPageSettingsService
{
    public function getSettingsFields()
    {
        $fields = array();


        $formSelect = new SelectField('form', null, ['label' => 'Formulaire à brancher']);
        $container = Container::getInstance();
        /** @var EntityManager $em */
        $em = $container->offsetGet('entityManager');
        $repository = $em->getRepository(ContactFormEntity::class);
        $forms = $repository->findAll();
        $opts = array(
            ''=>'Choisissez le formulaire à afficher'
        );
        if(!empty($forms)){ foreach($forms as $f){
            /** @var ContactFormEntity $f */
            $opts[$f->getId()] = $f->getName();
        }}
        $formSelect->setOptions($opts);

        $fields[] = $formSelect;

        return $fields;
    }

}
