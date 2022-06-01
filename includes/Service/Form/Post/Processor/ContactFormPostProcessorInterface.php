<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Processor;

use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostProcessingResult;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;

interface ContactFormPostProcessorInterface
{
    /**
     * @param ContactFormPostValidationResult $validationResult
     * @return ContactFormPostProcessingResult
     */
    public function process(ContactFormPostValidationResult $validationResult): ContactFormPostProcessingResult;
}
