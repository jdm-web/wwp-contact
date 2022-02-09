<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP;

use WonderWp\Component\Form\FormInterface;
use WonderWp\Plugin\Contact\Exception\NotFoundException;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\ContactFormPostValidatorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestValidator;
use WonderWp\Plugin\Core\Framework\ServiceResolver\DoctrineRepositoryServiceResolver;

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

        //Check if form exists
        /** @var ContactFormRepository $formRepository */
        $formRepository = $this->formRepositoryResolver->resolve();
        $formEntity         = $formRepository->find($requestData['id']);
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
        $res->setForm($formEntity);

        return $this->success($res);
    }

}
