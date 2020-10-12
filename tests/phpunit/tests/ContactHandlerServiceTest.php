<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\Form\Field\HoneyPotField;
use WonderWp\Component\Form\Form;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\Gateways\FakeMailer;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\ContactMailService;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;

class ContactHandlerServiceTest extends TestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    /** @var ContactManager */
    protected $manager;

    /** @var ContactHandlerService */
    protected $service;

    public function setUp()
    {
        $managerClass  = static::$managerClass;
        $this->manager = new $managerClass(static::$pluginName, static::$pluginVersion);
        $this->service = $this->manager->getService('contactHandler');
    }

    /*public function test_handleSubmit(){

    }

    public function test_handleFiles(){

    }*/

    public function test_setupMailDelivery_withHoneypot_should_return_fake_result()
    {
        $result            = new Result(200, ['type' => '_SuccessfulSubmitResult']);
        $data              = [HoneyPotField::HONEYPOT_FIELD_NAME => true];
        $stubContactEntity = $this->createMock(ContactEntity::class);
        $stubFormItem      = $this->createMock(ContactFormEntity::class);
        $mailService       = $this->manager->getService('mail');
        $mailer            = new FakeMailer();
        /** @var ContactEntity $stubContactEntity */
        /** @var ContactFormEntity $stubFormItem */
        /** @var ContactMailService $mailService */
        $res2 = $this->service->setupMailDelivery($result, $data, $stubContactEntity, $stubFormItem, $mailService, $mailer);
        $this->assertEquals($res2, $result);
    }

    public function test_setupMailDelivery_should_send_emails()
    {
        $result = new Result(200);
        $data   = [];

        $stubContactEntity = new ContactEntity();
        $formItem          = new ContactFormEntity();
        $formItem->setSendTo('jeremy.desvaux@wonderful.fr');
        $stubContactEntity->setForm($formItem);
        $stubContactEntity->setData(['mail' => 'jeremy.desvaux@wonderful.fr']);

        $mailService = $this->manager->getService('mail');
        $mailer      = new FakeMailer();
        /** @var ContactEntity $stubContactEntity */
        /** @var ContactFormEntity $stubFormItem */
        /** @var ContactMailService $mailService */
        $res2 = $this->service->setupMailDelivery($result, $data, $stubContactEntity, $formItem, $mailService, $mailer);

        $this->assertEquals($res2->getCode(), 200);
        $this->assertArrayHasKey('receiptResult', $res2->getData());
        $receiptRes = $res2->getData('receiptResult');
        $this->assertInstanceOf(Result::class, $receiptRes);
        $this->assertEquals($receiptRes->getCode(), 200);
    }

    public function test_saveContact_withHoneypot_should_return_fake_result()
    {
        $stubContactEntity    = $this->createMock(ContactEntity::class);
        $stubFormItem         = $this->createMock(ContactFormEntity::class);
        $persister            = $this->manager->getService('persister');
        $stubPersisterService = $this->createMock(get_class($persister));
        $result               = new Result(200);
        $data                 = [HoneyPotField::HONEYPOT_FIELD_NAME => true];

        /** @var ContactEntity $stubContactEntity */
        /** @var ContactFormEntity $stubFormItem */
        /** @var ContactPersisterService $stubPersisterService */
        $res2 = $this->service->saveContact($result, $data, $stubContactEntity, $stubFormItem, $stubPersisterService);
        $this->assertEquals($res2, $result);
    }

    public function test_saveContact_withoutFormSave_should_not_save_contact()
    {
        $stubContactEntity = new ContactEntity();
        $formItem          = new ContactFormEntity();
        $formItem->setSaveMsg(false);
        $stubContactEntity->setForm($formItem);

        $stubFormItem = $formItem;

        $persister            = $this->manager->getService('persister');
        $stubPersisterService = $this->createMock(get_class($persister));
        $result               = new Result(200);
        $data                 = [];

        /** @var ContactEntity $stubContactEntity */
        /** @var ContactFormEntity $stubFormItem */
        /** @var ContactPersisterService $stubPersisterService */
        $res2 = $this->service->saveContact($result, $data, $stubContactEntity, $stubFormItem, $stubPersisterService);
        $this->assertEquals($res2->getCode(), 200);
        $this->assertArrayHasKey('persist', $res2->getData());
        $persisterRes = $res2->getData('persist');
        $this->assertInstanceOf(Result::class, $persisterRes);
        $this->assertEquals($persisterRes->getData('msg'), 'No need to persist');
    }

    public function test_saveContact_withFormSave_should_save_contact()
    {
        $stubContactEntity = new ContactEntity();
        $formItem          = new ContactFormEntity();
        $formItem->setSaveMsg(true);
        $stubContactEntity->setForm($formItem);

        $stubFormItem = $formItem;

        $persister            = $this->manager->getService('persister');
        $stubPersisterService = $this->createMock(get_class($persister));

        $persistContactEntityRes = new Result(200, ['msg' => 'Entity persisted']);
        $stubPersisterService->expects($this->any())->method('persistContactEntity')->will($this->returnValue($persistContactEntityRes));

        $result = new Result(200);
        $data   = [];

        /** @var ContactEntity $stubContactEntity */
        /** @var ContactFormEntity $stubFormItem */
        /** @var ContactPersisterService $stubPersisterService */
        $res2 = $this->service->saveContact($result, $data, $stubContactEntity, $stubFormItem, $stubPersisterService);
        $this->assertEquals($res2->getCode(), 200);
        $this->assertArrayHasKey('persist', $res2->getData());
        $persisterRes = $res2->getData('persist');
        $this->assertInstanceOf(Result::class, $persisterRes);
        $this->assertEquals($persisterRes->getData('msg'), 'Entity persisted');
    }

}
