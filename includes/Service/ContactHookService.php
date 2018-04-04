<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\HoneyPotField;
use WonderWp\Component\Hook\AbstractHookService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Core\Service\WwpAdminChangerService;

/**
 * Class ContactHookService
 * @package WonderWp\Plugin\Contact
 * Defines the different hooks that are going to be used by your plugin
 */
class ContactHookService extends AbstractHookService
{

    /**
     * Run
     * @return $this
     */
    public function run()
    {

        //Get Manager
        $container     = Container::getInstance();
        $this->manager = $container->offsetGet('wwp-contact.Manager');

        /*
         * Admin Hooks
         */
        //Menus
        add_action('admin_menu', [$this, 'customizeMenus']);

        //Translate
        add_action('plugins_loaded', [$this, 'loadTextdomain']);

        //Send contact mail
        add_action('wwp-contact.contact_handler_service_success', [$this, 'setupMailDelivery'], 10, 3); //You can comment this to disable email delivery to debug

        //Save contact somewhere
        add_action('wwp-contact.contact_handler_service_success', [$this, 'saveContact'], 10, 3); //You can comment this to disable contact getting persisted

        //User deletion
        /** @var ContactUserDeleterService $deleterService */
        $deleterService = $this->manager->getService('userDeleter');
        //User deletion : on before confirmation screen
        add_action('delete_user_form', [$deleterService, 'deleteUserForm'], 10, 2);
        //User deletion : effective deletion
        add_action('delete_user',[$deleterService,'onUserBeforeDelete']);

        return $this;
    }

    /**
     * Add entry under top-level functionalities menu
     */
    public function customizeMenus()
    {

        //Get admin controller
        $adminController = $this->manager->getController(AbstractManager::ADMIN_CONTROLLER_TYPE);
        $callable        = [$adminController, 'route'];

        //Add entry under top-level functionalities menu
        $suffix = add_submenu_page('wonderwp-modules', 'Contact', 'Contact', WwpAdminChangerService::$DEFAULTMODULECAP, WWP_PLUGIN_CONTACT_NAME, $callable);

        add_action("admin_print_scripts-$suffix", [$this, 'my_plugin_admin_scripts']);
    }

    public function my_plugin_admin_scripts()
    {
        wp_enqueue_script('jquery-ui-sortable');
    }

    public function setupMailDelivery(Result $result, array $data, ContactEntity $contactEntity)
    {

        if(isset($data[HoneyPotField::HONEYPOT_FIELD_NAME]) && !empty($data[HoneyPotField::HONEYPOT_FIELD_NAME])){
            return new Result(200); //On fait croire que ca a marche
        }

        /** @var ContactMailService $mailService */
        $mailService = $this->manager->getService('mail');
        $result        = $mailService->sendContactMail($contactEntity, $data);
        if ($result->getCode() === 200) {
            $mailService->sendReceiptMail($contactEntity, $data);
        }
        return $result;
    }

    public function saveContact(Result $result, array $data, ContactEntity $contactEntity){

        if(isset($data[HoneyPotField::HONEYPOT_FIELD_NAME]) && !empty($data[HoneyPotField::HONEYPOT_FIELD_NAME])){
            return $result;
        }

        /** @var ContactPersisterService $persisterService */
        $persisterService = $this->manager->getService('persister');
        if($contactEntity->getForm()->getSaveMsg()){
            $persisterService->persistContactEntity($contactEntity);
        }
        return $result;
    }

}
