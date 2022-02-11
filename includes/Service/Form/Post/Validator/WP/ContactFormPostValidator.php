<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP;

use WonderWp\Component\Form\Field\FieldInterface;
use WonderWp\Component\Form\Field\FileField;
use WonderWp\Component\Form\Field\HoneyPotField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\Form\FormValidatorInterface;
use WonderWp\Plugin\Contact\Exception\BadRequestException;
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

    /** @var FormValidatorInterface * */
    protected $formValidator;

    /**
     * @param DoctrineRepositoryServiceResolver $formRepositoryResolver
     * @param ContactFormService $formService
     * @param DoctrineRepositoryServiceResolver $formFieldRepositoryResolver
     * @param FormInterface $formObject
     */
    public function __construct(
        DoctrineRepositoryServiceResolver $formRepositoryResolver,
        DoctrineRepositoryServiceResolver $formFieldRepositoryResolver,
        ContactFormService                $formService,
        FormInterface                     $formObject,
        FormValidatorInterface            $formValidator
    )
    {
        $this->formRepositoryResolver      = $formRepositoryResolver;
        $this->formFieldRepositoryResolver = $formFieldRepositoryResolver;
        $this->formService                 = $formService;
        $this->formObject                  = $formObject;
        $this->formValidator               = $formValidator;
    }

    /** @inheritDoc */
    public function validate(array $requestData, array $requestFiles = []): ContactFormPostValidationResult
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

        $this->formService->fillFormInstanceFromItem($this->formObject, $formEntity, $this->formFieldRepositoryResolver->resolve(), [], [$this->formService::honeypotFieldKey]);
        $validationData = $requestData;

        //Validate Files
        $validationData = $this->validateFiles($this->formObject->getFields(), $validationData, $requestFiles);

        //Validate Data
        $this->formValidator->setFormInstance($this->formObject);
        $errors = apply_filters('wwp-contact.contact_handler.validation_errors', $this->formValidator->validate($validationData, 'default'), $formEntity, $validationData, $this->formValidator);

        if (!empty($errors)) {
            $msgKey = ContactFormPostValidationResult::Error;
            $error  = new BadRequestException($msgKey, 400, null, $errors);
            return $this->error(new ContactFormPostValidationResult(
                400,
                $requestData,
                $msgKey,
                $validationData,
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

    /**
     * @param FieldInterface[] $fields
     * @param array $data
     *
     * @return array
     */
    protected function validateFiles(array $fields, array $data, array $files)
    {
        //Look for files
        if (!empty($fields)) {
            foreach ($fields as $f) {
                if ($f instanceof FileField) {
                    //Bot detection
                    if (!empty($data[$f->getName()])) {
                        //We have a posted value for this field : this shouldn't happen when using a file field properly which stores its data in $_FILES instead
                        //Do not process the file and pass the info in the data that this is a bot
                        $data[HoneyPotField::HONEYPOT_FIELD_NAME] = $data[$f->getName()]; //We don't want to make it too obvious
                    }

                    $name = str_replace(' ', '_', $f->getName());
                    $file = !empty($files[$name]) ? $files[$name] : null;

                    if (!empty($file)) {
                        $frags    = explode('.', $file['name']);
                        $ext      = end($frags);
                        $fileName = md5($file['name']) . '.' . $ext;
                    } else {
                        $fileName = null;
                    }
                    $data[$f->getName()] = $fileName;
                }
            }
        }

        return $data;
    }

}
