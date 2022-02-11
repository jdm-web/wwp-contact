<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP;

use WonderWp\Component\Form\Field\HoneyPotField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Plugin\Contact\Exception\NotFoundException;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;
use WonderWp\Plugin\Contact\Service\ContactTestDetector;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\ContactFormPostValidatorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestValidator;
use WonderWp\Plugin\Core\Framework\ServiceResolver\DoctrineRepositoryServiceResolver;
use WonderWp\Plugin\Security\Service\SecurityHookService;
use WonderWp\Plugin\Security\Service\SecurityIpService;

class ContactFormPostValidator extends ContactAbstractRequestValidator implements ContactFormPostValidatorInterface
{
    public static $ResultClass = ContactFormPostValidationResult::class;

    /** @var DoctrineRepositoryServiceResolver */
    protected $formRepositoryResolver;

    /** @var ContactFormService */
    protected $formService;

    /** @var DoctrineRepositoryServiceResolver */
    protected $formFieldRepositoryResolver;

    /** @var FormInterface */
    protected $formObject;

    /**
     * @param DoctrineRepositoryServiceResolver $formRepositoryResolver
     * @param ContactFormService $formService
     * @param DoctrineRepositoryServiceResolver $formFieldRepositoryResolver
     * @param FormInterface $formObject
     */
    public function __construct(DoctrineRepositoryServiceResolver $formRepositoryResolver, ContactFormService $formService, DoctrineRepositoryServiceResolver $formFieldRepositoryResolver, FormInterface $formObject)
    {
        $this->formRepositoryResolver      = $formRepositoryResolver;
        $this->formService                 = $formService;
        $this->formFieldRepositoryResolver = $formFieldRepositoryResolver;
        $this->formObject                  = $formObject;
    }


    public function validate(array $requestData): ContactFormPostValidationResult
    {
        //Check if form id is present in requestData
        $requiredParametersErrors = $this->checkRequiredParameters(['id'], $requestData);
        if (!empty($requiredParametersErrors)) {
            return $this->requiredParametersValidationResult($requestData, $requiredParametersErrors);
        }

        //Bot checking
        $ipAddress = ContactTestDetector::isUnitTesting() && !empty($requestData['ip']) ? $requestData['ip'] : SecurityIpService::getUserIpAddr();
        $isBot     = $this->isBot($requestData, $ipAddress);
        if ($isBot) {
            $res = new ContactFormPostValidationResult(200, $requestData, ContactFormPostValidationResult::Success);
            $res
                ->setIsBot($isBot);
            return $res;
        }

        //Check if form exists
        /** @var ContactFormRepository $formRepository */
        $formRepository = $this->formRepositoryResolver->resolve();
        $formEntity     = $formRepository->find($requestData['id']);
        if (empty($formEntity)) {
            $msgKey = ContactFormPostValidationResult::NotFound;
            $error  = new NotFoundException($msgKey, 404, null, ['id' => $requestData['id']]);
            return $this->error(new ContactFormPostValidationResult(
                404,
                $requestData,
                $msgKey,
                [],
                $error
            ));
        }

        //Else : all good
        $res = new ContactFormPostValidationResult(200, $requestData, ContactFormPostValidationResult::Success);
        $res
            ->setForm($formEntity)
            ->setIsBot($isBot);

        return $this->success($res);
    }

    public function isBot(array $requestData, $ip)
    {
        //Check honey pot Value
        $honeyPotValue = $requestData[HoneyPotField::HONEYPOT_FIELD_NAME] ?? null;

        //Check email Value
        $email = $requestData['mail'] ?? '';
        if (empty($email) && !empty($requestData['email'])) {
            $email = $requestData['email'];
        }

        //Check content Value
        $contentValue = $requestData['message'] ?? '';
        if (empty($contentValue) && !empty($requestData['msg'])) {
            $contentValue = $requestData['email'];
        }

        //Check phone value
        $phoneValue = $requestData['telephone'] ?? '';
        if (empty($phoneValue) && !empty($requestData['tel'])) {
            $phoneValue = $requestData['tel'];
        }
        if (empty($phoneValue) && !empty($requestData['phone'])) {
            $phoneValue = $requestData['phone'];
        }

        /** @see SecurityHookService::performBotAnalysis */
        return apply_filters('wwp-security.isBot', false, 'wwp-contact.isBotCheck', $honeyPotValue, $email, $ip, $contentValue, $phoneValue);
    }

}
