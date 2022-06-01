<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Validator;

use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;

interface ContactFormPostValidatorInterface
{
    /**
     * @param array $requestData
     * @return ContactFormPostValidationResult
     */
    public function validate(array $requestData): ContactFormPostValidationResult;
}
