<?php

namespace WonderWp\Plugin\Contact;

use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Service\ServiceInterface;
use WonderWp\Plugin\Contact\Controller\ContactAdminController;
use WonderWp\Plugin\Contact\Controller\ContactPublicController;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Form\ContactForm;
use WonderWp\Plugin\Contact\ListTable\ContactListTable;
use WonderWp\Plugin\Contact\Service\ContactActivator;
use WonderWp\Plugin\Contact\Service\ContactAssetService;
use WonderWp\Plugin\Contact\Service\ContactDoctrineEMLoaderService;
use WonderWp\Plugin\Contact\Service\ContactFormService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\ContactHookService;
use WonderWp\Plugin\Contact\Service\ContactMailService;
use WonderWp\Plugin\Contact\Service\ContactPageSettingsService;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;
use WonderWp\Plugin\Contact\Service\ContactRouteService;
use WonderWp\Plugin\Contact\Service\ContactUserDeleterService;
use WonderWp\Plugin\Contact\Service\Exporter\ContactCsvExporterService;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractDoctrinePluginManager;
use WonderWp\Plugin\Core\Framework\Doctrine\DoctrineEMLoaderServiceInterface;
use WonderWp\Plugin\Core\Framework\PageSettings\AbstractPageSettingsService;

/**
 * Class ContactManager
 * @package WonderWp\Plugin\Contact
 * The manager is the file that registers everything your plugin is going to use / need.
 * It's the most important file for your plugin, the one that bootstraps everything.
 * The manager registers itself with the DI container, so you can retrieve it somewhere else and use its config / controllers / services
 */
class ContactManager extends AbstractDoctrinePluginManager{

    const multipleAddressSeparator = ';';

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
        $this->setConfig('entityName',ContactFormEntity::class);
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
        $this->addService(DoctrineEMLoaderServiceInterface::DOCTRINE_EM_LOADER_SERVICE_NAME, function () {
            //Doctrine loader service
            return new ContactDoctrineEMLoaderService();
        });
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
        $this->addService(AbstractPageSettingsService::PAGE_SETTINGS_SERVICE_NAME,function(){
            //Page settings service
            return new ContactPageSettingsService();
        });
        $this->addService(ServiceInterface::ACTIVATOR_NAME, function () {
            //Activator
            return new ContactActivator(WWP_PLUGIN_CONTACT_VERSION);
        });
        /* //Uncomment this if your plugin has an api, then create the ContactApiService class in the include folder
        $this->addService(ServiceInterface::API_SERVICE_NAME,function(){
            //Api service
            return new ContactApiService();
        });*/
        $this->addService('form',function(){
            return new ContactFormService();
        });
        $this->addService('contactHandler',function(){
            return new ContactHandlerService();
        });
        $this->addService('mail',function(){
            return new ContactMailService();
        });
        $this->addService('persister',function(){
            return new ContactPersisterService();
        });
        $this->addService('exporter',function(){
            return new ContactCsvExporterService();
        });
        $this->addService('userDeleter',function(){
            $deleterService = new ContactUserDeleterService();
            //$deleterService->setManager($this);
            return $deleterService;
        });

        return $this;
    }

}
