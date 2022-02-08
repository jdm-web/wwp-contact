<?php

namespace WonderWp\Plugin\Contact\Service\Form\Read\Processor;

use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadProcessingResult;
use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadValidationResult;

interface ContactFormReadProcessorInterface
{
    /**
     * @param ContactFormReadValidationResult $validationResult
     * @return ContactFormReadProcessingResult
     */
    public function process(ContactFormReadValidationResult $validationResult): ContactFormReadProcessingResult;
}
