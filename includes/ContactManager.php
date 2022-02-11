<?php

namespace WonderWp\Plugin\Contact;

use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Service\ServiceInterface;
use WonderWp\Plugin\Contact\Controller\ContactAdminController;
use WonderWp\Plugin\Contact\Controller\ContactPublicController;
use WonderWp\Plugin\Contact\Email\ContactAdminEmail;
use WonderWp\Plugin\Contact\Email\ContactCustomerEmail;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Form\ContactForm;
use WonderWp\Plugin\Contact\ListTable\ContactFormListTable;
use WonderWp\Plugin\Contact\ListTable\ContactListTable;
use WonderWp\Plugin\Contact\Service\Mail\ContactMailHookHandler;
use WonderWp\Plugin\Contact\Service\Mail\ContactMailService;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Repository\ContactRepository;
use WonderWp\Plugin\Contact\Service\ContactActivator;
use WonderWp\Plugin\Contact\Service\ContactApiService;
use WonderWp\Plugin\Contact\Service\ContactAssetService;
use WonderWp\Plugin\Contact\Service\ContactCacheService;
use WonderWp\Plugin\Contact\Service\ContactCronService;
use WonderWp\Plugin\Contact\Service\ContactDoctrineEMLoaderService;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\ContactHookService;
use WonderWp\Plugin\Contact\Service\ContactPageSettingsService;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;
use WonderWp\Plugin\Contact\Service\ContactRgpdService;
use WonderWp\Plugin\Contact\Service\ContactRouteService;
use WonderWp\Plugin\Contact\Service\ContactUserDeleterService;
use WonderWp\Plugin\Contact\Service\ContactTaskService;
use WonderWp\Plugin\Contact\Service\Exporter\ContactCsvExporterService;
use WonderWp\Plugin\Contact\Service\Form\Post\Processor\WP\ContactFormPostProcessor;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP\ContactFormPostValidator;
use WonderWp\Plugin\Contact\Service\Form\Read\Processor\WP\ContactFormReadProcessor;
use WonderWp\Plugin\Contact\Service\Form\Read\Validator\WP\ContactFormReadValidator;
use WonderWp\Plugin\Contact\Service\Serializer\ContactJsonSerializer;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractDoctrinePluginManager;
use WonderWp\Plugin\Core\Framework\Doctrine\DoctrineEMLoaderServiceInterface;
use WonderWp\Plugin\Core\Framework\PageSettings\AbstractPageSettingsService;
use WonderWp\Plugin\Core\Framework\ServiceResolver\DoctrineRepositoryServiceResolver;
use WonderWp\Plugin\Core\Service\WwpAdminChangerService;

/**
 * Class ContactManager
 * @package WonderWp\Plugin\Contact
 * The manager is the file that registers everything your plugin is going to use / need.
 * It's the most important file for your plugin, the one that bootstraps everything.
 * The manager registers itself with the DI container, so you can retrieve it somewhere else and use its config / controllers / services
 */
class ContactManager extends AbstractDoctrinePluginManager
{

    const multipleAddressSeparator = ';';

    /**
     * Registers config, controllers, services etc usable by the plugin components
     *
     * @param Container $container
     *
     * @return $this
     */
    public function register(Container $container)
    {
        parent::register($container);

        /**
         * Config
         */
        $this->setConfig('path.root', plugin_dir_path(dirname(__FILE__)));
        $this->setConfig('path.base', dirname(dirname(plugin_basename(__FILE__))));
        $this->setConfig('path.url', plugin_dir_url(dirname(__FILE__)));
        $this->setConfig('entityName', ContactFormEntity::class);
        $this->setConfig('textDomain', WWP_CONTACT_TEXTDOMAIN);
        $this->setConfig('plugin.capability', $this->getConfig('plugin.capability', WwpAdminChangerService::$DEFAULTMODULECAP));
        $jsAssetGroup = is_env_webpack() ? 'plugins' : 'app';
        $this->setConfig('jsAssetGroup', $jsAssetGroup);
        $this->setConfig('contactEntityName', $this->getConfig('contactEntityName', ContactEntity::class));
        $this->setConfig('contactFormFieldEntityName', $this->getConfig('contactFormFieldEntityName', ContactFormFieldEntity::class));
        $this->setConfig('validator.translationDomain', $this->getConfig('validator.translationDomain', 'wonderwp_theme'));
        $this->setConfig('cache.types', $this->getConfig('cache.types', [
            $this->getConfig('entityName'),
            $this->getConfig('contactEntityName'),
            $this->getConfig('contactFormFieldEntityName'),
            $this->getConfig('path.base'),
        ]));
        $this->setConfig('stylesheetToLoad', $this->getConfig('stylesheetToLoad', '_contact.scss'));

        $this->setConfig('enableApi', $this->getConfig('enableApi', false));
        $enableApi = $this->getConfig('enableApi');

        //Emails
        $this->setConfig('ContactAdminEmailClass', $this->getConfig('ContactAdminEmailClass', ContactAdminEmail::class));
        $this->setConfig('ContactCustomerEmailClass', $this->getConfig('ContactCustomerEmailClass', ContactCustomerEmail::class));

        /**
         * Controllers
         */
        $this->addController(AbstractManager::ADMIN_CONTROLLER_TYPE, function () {
            return new ContactAdminController($this);
        });
        $this->addController(AbstractManager::PUBLIC_CONTROLLER_TYPE, function () {
            return $plugin_public = new ContactPublicController($this);
        });

        /**
         * Services
         */
        // Tasks / Command line commands
        $this->addService(ServiceInterface::COMMAND_SERVICE_NAME, function () {
            return new ContactTaskService();
        });
        //Hook service
        $this->addService(ServiceInterface::HOOK_SERVICE_NAME, function () {
            return new ContactHookService($this);
        });
        //Doctrine loader service
        $this->addService(DoctrineEMLoaderServiceInterface::DOCTRINE_EM_LOADER_SERVICE_NAME, function () {
            return new ContactDoctrineEMLoaderService();
        });
        //Model Form service
        $this->addService(ServiceInterface::MODEL_FORM_SERVICE_NAME, $container->factory(function () {
            return new ContactForm();
        }));
        //List Table service
        $this->addService(ServiceInterface::LIST_TABLE_SERVICE_NAME, function () {
            return new ContactFormListTable();
        });
        //List Table service
        $this->addService('msgListTable', function () {
            return new ContactListTable();
        });
        //Asset service
        $this->addService(ServiceInterface::ASSETS_SERVICE_NAME, function () {
            return new ContactAssetService($this);
        });
        //Route service
        $this->addService(ServiceInterface::ROUTE_SERVICE_NAME, function () {
            $rs = new ContactRouteService($this);

            return $rs;
        });
        //Page settings service
        $this->addService(AbstractPageSettingsService::PAGE_SETTINGS_SERVICE_NAME, function () {
            return new ContactPageSettingsService($this);
        });
        //Activator
        $this->addService(ServiceInterface::ACTIVATOR_NAME, function () {
            return new ContactActivator(WWP_PLUGIN_CONTACT_VERSION);
        });
        //Form service
        $this->addService('form', function () {
            return new ContactFormService($this);
        });
        //Contact Handler
        $this->addService('contactHandler', function () use ($container) {
            return new ContactHandlerService();
        });

        //Emails

        if (!isset($container['ContactMailerClass'])) {
            $container['ContactMailerClass'] = $container->factory(function ($container) {
                return $container['wwp.mailing.mailer'];
            });
        }

        $options   = [];
        $isTestEnv = defined('RUNNING_PHP_UNIT_TESTS');
        if ($isTestEnv) {
            $options = [
                'wonderwp_email_frommail' => 'jeremy.desvaux@wonderful.fr',
                'wonderwp_email_fromname' => 'Jeremy Desvaux',
                'wonderwp_email_tomail'   => 'jeremy.desvaux@wonderful.fr',
                'wonderwp_email_toname'   => 'Jeremy Desvaux',
                'site_name'               => 'Test Environment',
            ];
        } else {
            $keys = ['wonderwp_email_frommail', 'wonderwp_email_fromname', 'wonderwp_email_tomail', 'wonderwp_email_toname'];
            foreach ($keys as $key) {
                $options[$key] = get_option($key);
            }
            $options['site_name'] = get_bloginfo('name');
        }

        //Mail service
        $this->addService('mail', function () use ($container) {
            return new ContactMailService($this);
        });
        $this->addService('mailHookHandler', function () use ($container) {
            return new ContactMailHookHandler(
                $this->getService('mail')
            );
        });

        $this->addService(ContactAdminEmail::Name, $container->factory(function () use ($container, $options) {
            $serviceClass = $this->getConfig('ContactAdminEmailClass');
            return new $serviceClass(
                $container['ContactMailerClass'],
                $options['wonderwp_email_frommail'],
                $options['wonderwp_email_fromname'],
                $options['wonderwp_email_tomail'],
                $options['wonderwp_email_tomail'],
                $options['site_name']
            );
        }));
        $this->addService(ContactCustomerEmail::Name, $container->factory(function () use ($container, $options) {
            $serviceClass = $this->getConfig('ContactCustomerEmailClass');
            return new $serviceClass(
                $container['ContactMailerClass'],
                $options['wonderwp_email_frommail'],
                $options['wonderwp_email_fromname'],
                $options['wonderwp_email_tomail'],
                $options['wonderwp_email_tomail'],
                $options['site_name']
            );
        }));

        //Persister ?
        $this->addService('persister', function () {
            return new ContactPersisterService();
        });
        //Csv exporter
        $this->addService('exporter', function () use ($container) {
            return new ContactCsvExporterService($container['wwp.fileSystem']);
        });
        //User deleter service (called when a user is deleted from the BO so we can clean up its data)
        $this->addService('userDeleter', function () {
            $deleterService = new ContactUserDeleterService();

            //$deleterService->setManager($this);
            return $deleterService;
        });
        //Form repo
        $this->addService('contactFormRepository', function () {
            return new ContactFormRepository(null, null, $this->getConfig('entityName'));
        });
        //Msg repo
        $this->addService('messageRepository', function () {
            return new ContactRepository(null, null, $this->getConfig('contactEntityName'));
        });
        //Msg repo
        $this->addService('formFieldRepository', function () {
            return new ContactFormFieldRepository(null, null, $this->getConfig('contactFormFieldEntityName'));
        });
        //Rgpd service to interact with the rgpd plugin
        $this->addService('rgpd', function () {
            return new ContactRgpdService($this);
        });
        //Cache service
        $this->addService('cache', function () {
            return new ContactCacheService(
                $this->getService(ServiceInterface::HOOK_SERVICE_NAME),
                $this->getConfig('cache.types')
            );
        });
        //Cron
        $this->addService('cron', function () {
            return new ContactCronService();
        });

        if ($enableApi) {
            $this->addService('jsonSerializer', function () {
                return new ContactJsonSerializer(
                    $this->getService('form')
                );
            });
            $this->addService(ServiceInterface::API_SERVICE_NAME, function () {
                return new ContactApiService(
                    $this,
                    $this->getService('jsonSerializer')
                );
            });
        }
        $this->addService('contactFormReadValidator', $container->factory(function () {
            return new ContactFormReadValidator(
                new DoctrineRepositoryServiceResolver($this, 'contactFormRepository')
            );
        }));
        $this->addService('contactFormReadProcessor', $container->factory(function () {
            return new ContactFormReadProcessor(
                $this->getService('jsonSerializer')
            );
        }));
        $this->addService('contactFormPostValidator', $container->factory(function () use ($container) {
            return new ContactFormPostValidator(
                new DoctrineRepositoryServiceResolver($this, 'contactFormRepository'),
                new DoctrineRepositoryServiceResolver($this, 'formFieldRepository'),
                $this->getService('form'),
                $container['wwp.form.form'],
                $container['wwp.form.validator']
            );
        }));
        $this->addService('contactFormPostProcessor', $container->factory(function () {
            return new ContactFormPostProcessor(
                $this->getConfig('contactEntityName'),
                $this->getService('persister')
            );
        }));

        return $this;
    }

}
