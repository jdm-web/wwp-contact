<?php

namespace WonderWp\Plugin\Contact\Service\Mail;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Mailing\Gateways\FakeMailer;
use WonderWp\Plugin\Contact\Email\AbstractContactEmail;
use WonderWp\Plugin\Contact\Email\ContactAdminEmail;
use WonderWp\Plugin\Contact\Email\ContactCustomerEmail;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostProcessingResult;
use WonderWp\Plugin\Contact\Service\ContactTestDetector;

class ContactMailHookHandler
{
    /** @var ContactMailService */
    protected $mailService;

    /**
     * @param ContactMailService $mailService
     */
    public function __construct(ContactMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function contactCreationSendsAdminEmail(ContactFormPostProcessingResult $processingResult)
    {
        if ($processingResult->getCode() !== 200) {
            return $processingResult;
        }

        $this->handleIntegrationTesting($processingResult);

        /** @var ContactAdminEmail $adminEmail */
        $adminEmail = $this->mailService->getEmailFor(ContactAdminEmail::Name, $processingResult->getContactEntity());

        return $this->addSendableData($processingResult, $adminEmail);
    }

    public function contactCreationSendsCustomerEmail(ContactFormPostProcessingResult $processingResult)
    {
        if ($processingResult->getCode() !== 200 || !$processingResult->getContactEntity()->getForm()->isSendCustomerEmail()) {
            return $processingResult;
        }

        $this->handleIntegrationTesting($processingResult);

        /** @var ContactAdminEmail $adminEmail */
        $adminEmail = $this->mailService->getEmailFor(ContactCustomerEmail::Name, $processingResult->getContactEntity());

        return $this->addSendableData($processingResult, $adminEmail);
    }

    //=======================================================================================================//

    protected function addSendableData(AbstractRequestProcessingResult $processingResult, AbstractContactEmail $email)
    {

        $sendable             = $email::isSendable();
        $mailResult           = $sendable ? $email->send() : false;
        $processingResultData = $processingResult->getData();
        $dataMailResult       = $processingResultData['emails'] ?? [];

        $dataMailResult[$email::Name]   = [
            'isMailSendable' => $sendable,
            'sentResult'     => $mailResult
        ];
        $processingResultData['emails'] = $dataMailResult;

        $processingResult->setData($processingResultData);

        return $processingResult;
    }

    protected function handleIntegrationTesting(AbstractRequestProcessingResult $processingResult)
    {
        $origin               = $processingResult->getValidationResult()->getRequestData('origin', '');
        $isIntegrationTesting = ContactTestDetector::isIntegrationTesting($origin);
        $isUnitTesting        = ContactTestDetector::isUnitTesting();
        $isTesting            = ($isIntegrationTesting || $isUnitTesting);

        if ($isTesting) {
            //Prevent email delivery if we're in testing mode
            $container                       = Container::getInstance();
            $container['ContactMailerClass'] = $container->factory(function () {
                return new FakeMailer();
            });
        }
        $processingResult->addData(['integrationTesting' => $isTesting]);

        return $isTesting;
    }


}
