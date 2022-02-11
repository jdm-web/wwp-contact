<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit\Service\Form\Post\Validator\WP;

use PHPUnit\Framework\TestCase;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP\ContactFormPostValidator;
use WonderWp\Plugin\Security\SecurityManager;
use WonderWp\Plugin\Security\Service\SecurityHookService;

class ContactFormPostValidatorTest extends TestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    /** @var ContactManager */
    protected $manager;

    /** @var ContactFormPostValidator */
    protected $service;

    public function setUp(): void
    {
        $managerClass  = static::$managerClass;
        $this->manager = new $managerClass(static::$pluginName, static::$pluginVersion);
        $this->service = $this->manager->getService('contactFormPostValidator');

        $securityManager = new SecurityManager(WWP_PLUGIN_SECURITY_NAME,WWP_PLUGIN_SECURITY_VERSION);
        $securityHookService = new SecurityHookService($securityManager);
        $securityHookService->register();
    }

    public function test_missing_param_should_return_error_result()
    {
        $requestData      = [];
        $validationResult = $this->service->validate($requestData);
        $this->assertEquals(400, $validationResult->getCode());
    }

    public function test_bot_detection_with_rogue_IP_should_detect_bot()
    {
        $requestData      = [
            'id' => 1,
            'ip' => '127.0.0.1'
        ];
        $validationResult = $this->service->validate($requestData);

        //Assert bot detection
        $this->assertTrue($validationResult->isBot());
    }
}
