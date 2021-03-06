<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use WonderWp\Plugin\Contact\Email\ContactAdminEmail;
use WonderWp\Plugin\Contact\Email\ContactCustomerEmail;
use WonderWp\Plugin\Contact\Service\ContactCacheService;
use WonderWp\Plugin\Contact\Service\ContactCronService;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\Mail\ContactMailHookHandler;
use WonderWp\Plugin\Contact\Service\Mail\ContactMailService;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;
use WonderWp\Plugin\Contact\Service\ContactRgpdService;
use WonderWp\Plugin\Contact\Service\ContactUserDeleterService;
use WonderWp\Plugin\Contact\Service\Exporter\ContactCsvExporterService;
use WonderWp\Plugin\Contact\Service\Form\Post\Processor\WP\ContactFormPostProcessor;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP\ContactFormPostValidator;
use WonderWp\Plugin\Contact\Service\Form\Read\Processor\WP\ContactFormReadProcessor;
use WonderWp\Plugin\Contact\Service\Form\Read\Validator\WP\ContactFormReadValidator;
use WonderWp\Plugin\Contact\Service\Serializer\ContactJsonSerializer;
use WonderWp\Plugin\Core\Test\ManagerTestCase;

class ContactManagerTest extends ManagerTestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    protected function getServicesDefinitionsToTest()
    {
        $parentDefinitions = parent::getServicesDefinitionsToTest();

        return $parentDefinitions + [
                'form'                     => ContactFormService::class,
                'mail'                     => ContactMailService::class,
                'persister'                => ContactPersisterService::class,
                'exporter'                 => ContactCsvExporterService::class,
                'userDeleter'              => ContactUserDeleterService::class,
                'rgpd'                     => ContactRgpdService::class,
                'cache'                    => ContactCacheService::class,
                'cron'                     => ContactCronService::class,
                'jsonSerializer'           => ContactJsonSerializer::class,
                'contactFormReadValidator' => ContactFormReadValidator::class,
                'contactFormReadProcessor' => ContactFormReadProcessor::class,
                'contactFormPostValidator' => ContactFormPostValidator::class,
                'contactFormPostProcessor' => ContactFormPostProcessor::class,
                'mailHookHandler'          => ContactMailHookHandler::class,
                'ContactAdminEmail'        => ContactAdminEmail::class,
                'ContactCustomerEmail'     => ContactCustomerEmail::class
            ];
    }

}
