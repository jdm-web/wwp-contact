<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\Form\Field\HiddenField;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;

class ContactFormServiceTest extends TestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    /** @var ContactManager */
    protected $manager;

    /** @var ContactFormService */
    protected $service;

    public function setUp():void
    {
        $managerClass  = static::$managerClass;
        $this->manager = new $managerClass(static::$pluginName, static::$pluginVersion);
        $this->service = $this->manager->getService('form');
    }

    public function test_getOtherNecessaryFields_without_post_should_return_an_array_of_valid_fields()
    {
        $formItem = new ContactFormEntity();
        $formItem->setId(1);

        $extraFields = $this->service->getOtherNecessaryFields($formItem);

        $this->assertArrayHasKey('form', $extraFields);
        $this->assertArrayHasKey('nonce', $extraFields);
        $this->assertArrayHasKey('honeypot', $extraFields);

        //post field is created but with 0 as value
        $this->assertArrayHasKey('post', $extraFields);
        $postField = $extraFields['post'];
        $val       = $postField->getValue();
        $this->assertEquals(0, $val);
    }

    public function test_getOtherNecessaryFields_with_post_should_return_an_array_of_valid_fields()
    {
        $formItem = new ContactFormEntity();
        $formItem->setId(1);

        $extraFields = $this->service->getOtherNecessaryFields($formItem, 1);

        $this->assertArrayHasKey('post', $extraFields);
        /** @var HiddenField $postField */
        $postField = $extraFields['post'];
        $val       = $postField->getValue();
        $this->assertEquals(1, $val);
    }
}
