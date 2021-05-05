<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Core\Cache\AbstractCacheServiceTest;

class ContactCacheServiceTest extends AbstractCacheServiceTest
{
    protected static $managerClass = WWP_PLUGIN_CONTACT_MANAGER;
    protected static $pluginName = WWP_PLUGIN_CONTACT_NAME;
    protected static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    public function testIsConcernedShouldReturnCorrectBoolean()
    {
        $this->assertTrue($this->service->isConcerned($this->manager->getConfig('entityName')));
        $this->assertTrue($this->service->isConcerned($this->manager->getConfig('contactEntityName')));
        $this->assertTrue($this->service->isConcerned($this->manager->getConfig('contactFormFieldEntityName')));
        $this->assertFalse($this->service->isConcerned(ContactManager::class));
    }
}
