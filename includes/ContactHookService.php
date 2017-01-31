<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 25/08/2016
 * Time: 17:02
 */

namespace WonderWp\Plugin\Contact;

use WonderWp\APlugin\AbstractManager;
use WonderWp\APlugin\AbstractPluginManager;
use WonderWp\DI\Container;
use WonderWp\Hooks\AbstractHookService;
use WonderWp\Plugin\WwpAdminChangerService;

/**
 * Class ContactHookService
 * @package WonderWp\Plugin\Contact
 * Defines the different hooks that are going to be used by your plugin
 */
class ContactHookService extends AbstractHookService{

    /**
     * Run
     * @return $this
     */
    public function run(){

        //Get Manager
        $container = Container::getInstance();
        $this->_manager = $container->offsetGet('wwp-contact.Manager');

        /*
         * Admin Hooks
         */
        //Menus
        add_action( 'admin_menu', array($this, 'customizeMenus' ));

        //Translate
        add_action( 'plugins_loaded', array($this, 'loadTextdomain' ));

        return $this;
    }

    /**
     * Add entry under top-level functionalities menu
     */
    public function customizeMenus(){

        //Get admin controller
        $adminController = $this->_manager->getController(AbstractManager::$ADMINCONTROLLERTYPE);
        $callable = array($adminController,'route');

        //Add entry under top-level functionalities menu
        $suffix = add_submenu_page('wonderwp-modules', 'Contact', 'Contact', WwpAdminChangerService::$DEFAULTMODULECAP, WWP_PLUGIN_CONTACT_NAME, $callable);

        add_action( "admin_print_scripts-$suffix", array($this,'my_plugin_admin_scripts'));
    }

    /**
     * Load Textdomain
     */
    public function loadTextdomain()
    {
        $languageDir = $this->_manager->getConfig('path.base') . '/languages/';
        load_plugin_textdomain($this->_manager->getConfig('textDomain'),false,$languageDir);
    }

    public function my_plugin_admin_scripts(){
        wp_enqueue_script( 'jquery-ui-sortable' );
    }

}