<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use WonderWp\Plugin\Core\Cache\AbstractCacheServiceTest;

class ContactCacheServiceTest extends AbstractCacheServiceTest
{
    protected static $managerClass = WWP_PLUGIN_CONTACT_MANAGER;
    protected static $pluginName = WWP_PLUGIN_CONTACT_NAME;
    protected static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;
}
