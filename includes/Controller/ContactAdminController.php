<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://digital.wonderful.fr
 * @since      1.0.0
 *
 * @package    Wonderwp
 * @subpackage Wonderwp/admin
 */

namespace WonderWp\Plugin\Contact\Controller;

use WonderWp\Framework\AbstractPlugin\AbstractListTable;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Form\FormViewReadOnly;
use WonderWp\Framework\HttpFoundation\Request;
use WonderWp\Framework\Template\Views\VueFrag;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Form\ContactForm;
use WonderWp\Plugin\Contact\Form\ContactFormFieldForm;
use WonderWp\Plugin\Contact\Form\ContactFormForm;
use WonderWp\Plugin\Contact\ListTable\ContactFormFieldListTable;
use WonderWp\Plugin\Contact\ListTable\ContactFormListTable;
use WonderWp\Plugin\Contact\ListTable\ContactListTable;
use WonderWp\Plugin\Contact\Service\Exporter\ContactExporterServiceInterface;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractPluginDoctrineBackendController;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wonderwp
 * @subpackage Wonderwp/admin
 * @author     Wonderful <jeremy.desvaux@wonderful.fr>
 */
class ContactAdminController extends AbstractPluginDoctrineBackendController
{
    /** @inheritdoc */
    public function getTabs()
    {
        $tabs = [
            1 => ['action' => 'list', 'libelle' => 'Gestion des formulaire'],
            2 => ['action' => 'listFields', 'libelle' => 'Gestion des champs'],
        ];

        return $tabs;
    }

    public function listAction(AbstractListTable $listTableInstance = null)
    {
        $listTableInstance = new ContactFormListTable();
        $listTableInstance->setEntityName(ContactFormEntity::class);
        $listTableInstance->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        parent::listAction($listTableInstance);
    }

    public function editContactFormAction()
    {
        $modelForm = new ContactFormForm();
        parent::editAction(ContactFormEntity::class, $modelForm);
    }

    public function listFieldsAction()
    {
        $listTable = new ContactFormFieldListTable();
        $listTable->setEntityName(ContactFormFieldEntity::class);
        $listTable->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        parent::listAction($listTable);
    }

    public function editContactFormFieldAction()
    {
        $modelForm = new ContactFormFieldForm();

        parent::editAction(ContactFormFieldEntity::class, $modelForm);
    }

    public function deleteContactFormFieldAction()
    {
        parent::deleteAction(ContactFormFieldEntity::class);
    }

    public function listmsgAction()
    {
        $listTable = new ContactListTable();
        $listTable->setEntityName(ContactEntity::class);
        $listTable->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        parent::listAction($listTable);
    }

    public function editContactAction()
    {
        /** @var Container $container */
        $container                       = Container::getInstance();
        $container['wwp.forms.formView'] = $container->factory(function () {
            return new FormViewReadOnly();
        });
        $modelForm                       = new ContactForm();
        parent::editAction(ContactEntity::class, $modelForm);
    }

    public function deleteContactAction()
    {
        parent::deleteAction(ContactEntity::class);
    }

    public function exportMsgAction()
    {
        $request = Request::getInstance();
        /** @var Container $container */
        $container = Container::getInstance();
        /** @var ContactManager $manager */
        $manager = $container[WWP_PLUGIN_CONTACT_NAME . '.Manager'];
        $repo    = $this->getRepository(ContactFormEntity::class);

        //Get form item
        $contactFormEntity = $repo->find($request->get('form'));
        if ($contactFormEntity instanceof ContactFormEntity) {

            /** @var ContactExporterServiceInterface $exporterService */
            $exporterService = $manager->getService('exporter');
            $exporterService->setFormInstance($contactFormEntity);
            $res = $exporterService->export();

            $prefix = $manager->getConfig('prefix');
            $container
                ->offsetGet('wwp.views.baseAdmin')
                ->registerFrags($prefix, [
                    new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.header')),
                    new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.tabs')),
                    new VueFrag($manager->getConfig('path.root') . '/admin/pages/export-result.php'),
                    new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.footer')),
                ])
                ->render([
                    'title'     => get_admin_page_title(),
                    'tabs'      => $this->getTabs(),
                    'uploadRes' => $res,
                ])
            ;
        }
    }

}
