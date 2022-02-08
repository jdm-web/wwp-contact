<?php

namespace WonderWp\Plugin\Contact\Service\Request;

use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

interface ContactRequestValidatorInterface
{
    /**
     * @param array $requestData
     * @return AbstractRequestValidationResult
     */
    public function validate(array $requestData): AbstractRequestValidationResult;
}
