<?php

namespace WonderWp\Plugin\Contact\Service\Request;

use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

interface ContactRequestProcessorInterface
{
    /**
     * @param AbstractRequestValidationResult $validationResult
     * @return AbstractRequestProcessingResult
     */
    public function process(AbstractRequestValidationResult $validationResult): AbstractRequestProcessingResult;
}
