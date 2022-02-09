<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Processor\WP;

use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostProcessingResult;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;
use WonderWp\Plugin\Contact\Service\Form\Post\Processor\ContactFormPostProcessorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestProcessor;

class ContactFormPostProcessor extends ContactAbstractRequestProcessor implements ContactFormPostProcessorInterface
{
    public static $ResultClass = ContactFormPostProcessingResult::class;

    public function process(ContactFormPostValidationResult $validationResult): ContactFormPostProcessingResult
    {
        if ($this->isValidationResultInvalid($validationResult)) {
            return $this->processingResultFromValidationResult($validationResult);
        }
        $formItem = $validationResult->getForm();

        // TODO: Implement process() method.
        return $this->success(new ContactFormPostProcessingResult(
            501,
            $validationResult,
            ContactFormPostProcessingResult::Error
        ));
    }

}
