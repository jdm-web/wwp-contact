<?php

namespace WonderWp\Plugin\Contact\Service\Form\Read\Processor\WP;

use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadProcessingResult;
use WonderWp\Plugin\Contact\Result\Form\Read\ContactFormReadValidationResult;
use WonderWp\Plugin\Contact\Service\Form\Read\Processor\ContactFormReadProcessorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestProcessor;
use WonderWp\Plugin\Contact\Service\Serializer\ContactSerializerInterface;

class ContactFormReadProcessor extends ContactAbstractRequestProcessor implements ContactFormReadProcessorInterface
{
    public static $ResultClass = ContactFormReadProcessingResult::class;

    /** @var ContactSerializerInterface */
    protected $serializer;

    /**
     * @param ContactSerializerInterface $serializer
     */
    public function __construct(ContactSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function process(ContactFormReadValidationResult $validationResult): ContactFormReadProcessingResult
    {
        if ($this->isValidationResultInvalid($validationResult)) {
            return $this->processingResultFromValidationResult($validationResult);
        }

        $formItem = $validationResult->getForm();
        $readResult = $this->serializer->unserialize($formItem);

        $result = new ContactFormReadProcessingResult(
            200,
            $validationResult,
            ContactFormReadProcessingResult::Success
        );
        $result
            ->setForm($formItem)
            ->setSerializedForm($readResult->getData());

        return $this->success($result);
    }

}
