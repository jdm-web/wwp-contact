<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use WonderWp\Component\Repository\PostRepository;
use WonderWp\Plugin\Contact\Service\ContactFormService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\ContactMailService;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;
use WonderWp\Plugin\Contact\Service\ContactUserDeleterService;
use WonderWp\Plugin\Contact\Service\Exporter\ContactCsvExporterService;
use WonderWp\Plugin\Core\Test\ManagerTestCase;
use WonderWp\Plugin\Map\Service\MapJsonSerialiserService;
use WonderWp\Plugin\Meteo\Gateways\OpenWeatherGateway;
use WonderWp\Plugin\Newsletter\Service\NewsletterPasserelleService;
use WonderWp\Plugin\Newsletter\Service\NewsletterUserDeleterService;

class ContactManagerTest extends ManagerTestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    protected function getServicesDefinitionsToTest()
    {
        $parentDefinitions = parent::getServicesDefinitionsToTest();

        return $parentDefinitions + [
                'form' => ContactFormService::class,
                //'contactHandler' => ContactHandlerService::class,
                'mail'=>ContactMailService::class,
                'persister'=>ContactPersisterService::class,
                //'exporter' =>ContactCsvExporterService::class,
                'userDeleter' => ContactUserDeleterService::class
            ];
    }

}
