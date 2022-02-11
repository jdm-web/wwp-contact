<?php

namespace WonderWp\Plugin\Contact\Service\Form\Read\Validator\WP;

use WonderWp\Plugin\Contact\Exception\NotFoundException;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadValidationResult;
use WonderWp\Plugin\Contact\Service\Form\Read\Validator\ContactFormReadValidatorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestValidator;
use WonderWp\Plugin\Core\Framework\ServiceResolver\DoctrineRepositoryServiceResolver;

class ContactFormReadValidator extends ContactAbstractRequestValidator implements ContactFormReadValidatorInterface
{
    public static $ResultClass = ContactFormReadValidationResult::class;

    /** @var DoctrineRepositoryServiceResolver */
    protected $formRepositoryResolver;

    /**
     * @param DoctrineRepositoryServiceResolver $formRepositoryResolver
     */
    public function __construct(DoctrineRepositoryServiceResolver $formRepositoryResolver)
    {
        $this->formRepositoryResolver = $formRepositoryResolver;
    }

    /** @inerhitDoc */
    public function validate(array $requestData, array $requestFiles = []): ContactFormReadValidationResult
    {
        //Check if form id is present in requestData
        $requiredParametersErrors = $this->checkRequiredParameters(['id'], $requestData);
        if (!empty($requiredParametersErrors)) {
            return $this->requiredParametersValidationResult($requestData, $requiredParametersErrors);
        }

        //Check if form exists
        /** @var ContactFormRepository $formRepository */
        $formRepository = $this->formRepositoryResolver->resolve();
        $form           = $formRepository->find($requestData['id']);
        if (empty($form)) {
            $msgKey = ContactFormReadValidationResult::NotFound;
            $error  = new NotFoundException($msgKey, 404, null, ['id' => $requestData['id']]);
            return $this->error(new ContactFormReadValidationResult(
                404,
                $requestData,
                $msgKey,
                [],
                $error
            ));
        }

        //Else : all good
        $res = new ContactFormReadValidationResult(200, $requestData, ContactFormReadValidationResult::Success);
        $res->setForm($form);

        return $this->success($res);
    }

}
