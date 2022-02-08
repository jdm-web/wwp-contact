<?php

namespace WonderWp\Plugin\Contact\Service\Form\Read\Validator;

use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadValidationResult;

interface ContactFormReadValidatorInterface
{
    /**
     * @param array $requestData
     * @return ContactFormReadValidationResult
     */
    public function validate(array $requestData): ContactFormReadValidationResult;
}
