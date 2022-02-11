<?php

namespace WonderWp\Plugin\Contact\Service\Request;

use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

interface ContactRequestValidatorInterface
{
    /**
     * @param array $requestData
     * @param array $requestFiles
     * @return AbstractRequestValidationResult
     */
    public function validate(array $requestData, array $requestFiles = []): AbstractRequestValidationResult;
}
