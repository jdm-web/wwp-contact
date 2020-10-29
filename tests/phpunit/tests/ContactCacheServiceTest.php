<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use PHPUnit\Framework\TestCase;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Service\ContactCacheService;

class ContactCacheServiceTest extends TestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    /** @var ContactManager */
    protected $manager;

    /** @var ContactCacheService */
    protected $service;

    public function setUp(): void
    {
        $managerClass  = static::$managerClass;
        $this->manager = new $managerClass(static::$pluginName, static::$pluginVersion);
        $this->service = $this->manager->getService('cache');
    }

    public function test_isEntityNameConcerned_should_return_correct_boolean()
    {
        $this->assertTrue($this->service->isEntityNameConcerned($this->manager->getConfig('entityName')));
        $this->assertTrue($this->service->isEntityNameConcerned($this->manager->getConfig('contactEntityName')));
        $this->assertTrue($this->service->isEntityNameConcerned($this->manager->getConfig('contactFormFieldEntityName')));
        $this->assertFalse($this->service->isEntityNameConcerned(ContactManager::class));
    }
}
