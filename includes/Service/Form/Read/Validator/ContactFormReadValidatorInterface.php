<?php

namespace WonderWp\Plugin\Contact\Service\Form\Read\Validator;

use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadValidationResult;

interface ContactFormReadValidatorInterface
{
    /**
     * @param array $requestData
     * @param array $requestFiles
     * @return ContactFormReadValidationResult
     */
    public function validate(array $requestData, array $requestFiles = []): ContactFormReadValidationResult;
}
