<?php

namespace WonderWp\Plugin\Contact\Service;

use Symfony\Component\HttpFoundation\ParameterBag;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\Hook\AbstractHookService;
use WonderWp\Component\PluginSkeleton\Exception\ControllerNotFoundException;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostProcessingResult;
use WonderWp\Plugin\Contact\Service\Mail\ContactMailHookHandler;
use WonderWp\Plugin\Core\Cache\CacheHookServiceTrait;

/**
 * Class ContactHookService
 * @package WonderWp\Plugin\Contact
 * Defines the different hooks that are going to be used by your plugin
 */
class ContactHookService extends AbstractHookService
{
    use CacheHookServiceTrait;

    /**
     * Run
     * @return $this
     * @throws ServiceNotFoundException
     */
    public function register()
    {

        /*
         * Admin Hooks
         */
        //Menus
        $this->addAction('admin_menu', [$this, 'customizeMenus']);

        //Translate
        $this->addAction('plugins_loaded', [$this, 'loadTextdomain']);

        //Send contact mail
        $this->addAction(ContactFormPostProcessingResult::Success, [$this, 'setupMailDelivery'], 10, 4); //You can comment this to disable email delivery to debug
        //$this->addAction('wp_mail_failed',[$this,'displayMailerError']); // When debugging an email, this function provides more information about why a mail could fail
        $this->addFilter('wwp-contact.form.toMail', [$this, 'checkForPotentialMailDestInSubject'], 11, 3);

        //User deletion
        /** @var ContactUserDeleterService $deleterService */
        $deleterService = $this->manager->getService('userDeleter');
        //User deletion : on before confirmation screen
        $this->addAction('delete_user_form', [$deleterService, 'deleteUserForm'], 10, 2);
        //User deletion : effective deletion
        $this->addAction('delete_user', [$deleterService, 'onUserBeforeDelete']);

        //Rgpd
        /** @var ContactRgpdService $rgpdService */
        $rgpdService = $this->manager->getService('rgpd');
        $this->addFilter('rgpd.consents', [$rgpdService, 'listConsents'], 10, 2);
        $this->addFilter('rgpd.consents.deletion', [$rgpdService, 'deleteConsents'], 10, 3);
        $this->addFilter('rgpd.consents.export', [$rgpdService, 'exportConsents'], 10, 2);
        $this->addFilter('rgpd.inventory', [$rgpdService, 'dataInventory']);

        //Cache
        $this->registerCacheHooks();

        //Doctrine
        $this->addFilter('wwp.plugin.registered-doctrine-plugin', [$this, 'registerPlugin']);

        //Crons
        $this->addFilter('cron.inventory', [$this, 'cronInventory']);

        return $this;
    }

    /**
     * Add entry under top-level functionalities menu
     * @throws ControllerNotFoundException
     */
    public function customizeMenus()
    {

        //Get admin controller
        $adminController = $this->manager->getController(AbstractManager::ADMIN_CONTROLLER_TYPE);
        $callable        = [$adminController, 'route'];

        //Add entry under top-level functionalities menu
        $suffix = add_submenu_page('wonderwp-modules', 'Contact', 'Contact', $this->manager->getConfig('plugin.capability'), WWP_PLUGIN_CONTACT_NAME, $callable);

        $this->addAction("admin_print_scripts-$suffix", [$this, 'my_plugin_admin_scripts']);
    }

    public function my_plugin_admin_scripts()
    {
        wp_enqueue_script('jquery-ui-sortable');
    }

    /**
     * @param ContactFormPostProcessingResult $result
     * @param array $data
     * @param ContactEntity $contactEntity
     * @param ContactFormEntity $formItem
     *
     * @return Result
     * @throws ServiceNotFoundException
     */
    public function setupMailDelivery(ContactFormPostProcessingResult $result)
    {
        /** @var ContactMailHookHandler $mailHookHandler */
        $mailHookHandler = $this->manager->getService('mailHookHandler');

        $result = $mailHookHandler->contactCreationSendsAdminEmail($result);
        $result = $mailHookHandler->contactCreationSendsCustomerEmail($result);

        return $result;
    }

    public function checkForPotentialMailDestInSubject($toMail, ContactEntity $contactEntity, array $data)
    {
        $isTestEnv = defined('RUNNING_PHP_UNIT_TESTS');
        if ($isTestEnv) {
            return $toMail;
        }

        /** @var ContactFormFieldRepository $fieldRepo */
        $fieldRepo = $this->manager->getService('formFieldRepository');
        /** @var ContactMailService $mailService */
        $mailService = $this->manager->getService('mail');
        $subjectDest = $mailService->findDestViaSubjectData($contactEntity, $data, $fieldRepo);
        if (!empty($subjectDest)) {
            $toMail = $subjectDest;
        }

        return $toMail;
    }

    // When debugging an email, this function provides more information about why a mail could fail, triggered by the wp_mail_failed hook
    public function displayMailerError($error)
    {
        print_r($error);
    }

    /**
     * @inheritDoc
     */
    public function loadTextdomain($domain = '', $locale = '', $languageDir = '')
    {
        $loaded = parent::loadTextdomain($domain, $locale, $languageDir);

        //Trads to JS
        $keysToProvide = ['contactPickerLabel', 'themeContactPickerDefaultLabel'];
        //Any key provided ?

        if (!empty($keysToProvide)) {
            //yes : get i18n from js config
            $container = Container::getInstance();
            /** @var ParameterBag $jsConfig */
            $jsConfig = !empty($container['jsConfig']) ? $container['jsConfig'] : new ParameterBag();
            if (!$jsConfig->get('i18n')) {
                $jsConfig->add(['i18n' => []]);
            }
            $i18n = $jsConfig->get('i18n');

            $i18n['contact'] = [];

            //add the provided keys
            foreach ($keysToProvide as $keyToProvide) {
                $i18n['contact'][$keyToProvide] = __($keyToProvide, WWP_CONTACT_TEXTDOMAIN);
            }

            //reassign i18n
            $jsConfig->set('i18n', $i18n);
            $container->offsetSet('jsConfig', $jsConfig);
        }

        return $loaded;
    }

    public function registerPlugin($plugins)
    {
        array_push($plugins, $this->manager->getConfig('path.base'));

        return $plugins;
    }

    public function cronInventory(array $cronInventory)
    {
        /** @var ContactCronService $cronService */
        $cronService = $this->manager->getService('cron');

        $cronInventory[$cronService::TYPE] = $cronService->getCronInventory();

        return $cronInventory;
    }

}
