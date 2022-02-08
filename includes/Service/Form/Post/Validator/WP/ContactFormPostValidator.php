<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP;

use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\ContactFormPostValidatorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestValidator;

class ContactFormPostValidator extends ContactAbstractRequestValidator implements ContactFormPostValidatorInterface
{
    public static $ResultClass = ContactFormPostValidationResult::class;

    public function validate(array $requestData): ContactFormPostValidationResult
    {
        // TODO: Implement validate() method.
        return new ContactFormPostValidationResult(
            200,
            $requestData,
            ContactFormPostValidationResult::Success
        );
    }

}
