<?php

namespace WonderWp\Plugin\Contact;

use WonderWp\Framework\AbstractPlugin\AbstractManager;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Service\ServiceInterface;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Form\ContactForm;
use WonderWp\Plugin\Contact\ListTable\ContactListTable;
use WonderWp\Plugin\Contact\Service\ContactAssetService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\ContactHookService;
use WonderWp\Plugin\Contact\Service\ContactPageSettingsService;
use WonderWp\Plugin\Contact\Service\ContactRouteService;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractDoctrinePluginManager;
use WonderWp\Plugin\Core\Framework\PageSettings\AbstractPageSettingsService;

/**
 * Class ContactManager
 * @package WonderWp\Plugin\Contact
 * The manager is the file that registers everything your plugin is going to use / need.
 * It's the most important file for your plugin, the one that bootstraps everything.
 * The manager registers itself with the DI container, so you can retrieve it somewhere else and use its config / controllers / services
 */
class ContactManager extends AbstractDoctrinePluginManager{

    /**
     * Registers config, controllers, services etc usable by the plugin components
     * @param Container $container
     * @return $this
     */
    public function register(Container $container)
    {
        parent::register($container);

        //Register Config
        $this->setConfig('path.root',plugin_dir_path( dirname( __FILE__ ) ));
        $this->setConfig('path.base',dirname( dirname( plugin_basename( __FILE__ ) ) ));
        $this->setConfig('path.url',plugin_dir_url( dirname( __FILE__ ) ));
        $this->setConfig('entityName',ContactEntity::class);
        $this->setConfig('textDomain',WWP_CONTACT_TEXTDOMAIN);

        //Register Controllers
        $this->addController(AbstractManager::ADMIN_CONTROLLER_TYPE,function(){
            return new ContactAdminController( $this );
        });
        $this->addController(AbstractManager::PUBLIC_CONTROLLER_TYPE,function(){
            return $plugin_public = new ContactPublicController($this);
        });

        //Register Services
        $this->addService(ServiceInterface::HOOK_SERVICE_NAME,$container->factory(function(){
            //Hook service
            return new ContactHookService();
        }));
        $this->addService(ServiceInterface::MODEL_FORM_SERVICE_NAME,$container->factory(function(){
            //Model Form service
            return new ContactForm();
        }));
        $this->addService(ServiceInterface::LIST_TABLE_SERVICE_NAME, function(){
            //List Table service
            return new ContactListTable();
        });
        $this->addService(ServiceInterface::ASSETS_SERVICE_NAME,function(){
            //Asset service
            return new ContactAssetService();
        });
        $this->addService(ServiceInterface::ROUTE_SERVICE_NAME,function(){
            //Route service
            return new ContactRouteService();
        });
        //Uncomment this if your plugin has page settings, then create the ContactPageSettingsService class in the include folder
        $this->addService(AbstractPageSettingsService::PAGE_SETTINGS_SERVICE_NAME,function(){
            //Page settings service
            return new ContactPageSettingsService();
        });
        /* //Uncomment this if your plugin has an api, then create the ContactApiService class in the include folder
        $this->addService(ServiceInterface::API_SERVICE_NAME,function(){
            //Api service
            return new ContactApiService();
        });*/
        $this->addService('contactHandler',function(){
            //Page settings service
            return new ContactHandlerService();
        });

        return $this;
    }

}
