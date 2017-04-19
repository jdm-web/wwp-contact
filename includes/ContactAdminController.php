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

namespace WonderWp\Plugin\Contact;

use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Form\ContactFormFieldForm;
use WonderWp\Plugin\Contact\Form\ContactFormForm;
use WonderWp\Plugin\Contact\ListTable\ContactFormFieldListTable;
use WonderWp\Plugin\Contact\ListTable\ContactFormListTable;
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
        $tabs = array(
            1 => array('action' => 'list', 'libelle' => 'Liste des messages'),
            2 => array('action' => 'listForms', 'libelle' => 'Gestion des formulaire'),
            3 => array('action' => 'listFields', 'libelle' => 'Gestion des champs'),
        );

        return $tabs;
    }

    public function listFormsAction()
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
}
