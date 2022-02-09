<?php

namespace WonderWp\Plugin\Contact\Service\Serializer;

use WonderWp\Component\Form\Form;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\ContactSerializeResult;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;

class ContactJsonSerializer implements ContactSerializerInterface
{
    /** @var ContactFormService */
    protected $formService;

    /**
     * @param ContactFormService $formService
     */
    public function __construct(ContactFormService $formService)
    {
        $this->formService = $formService;
    }


    public function serialize(ContactFormEntity $contactFormEntity): ContactSerializeResult
    {
        return new ContactSerializeResult(500, ['msg' => 'Method not implemented']);
    }

    public function unserialize(ContactFormEntity $contactFormEntity): ContactSerializeResult
    {
        $formsData  = $this->formService->prepareViewParams($contactFormEntity);
        $resultData = [
            'item'     => $this->serializeFormItem($formsData['item']),
            'instance' => $this->serializeFormInstance($formsData['instance']),
            'viewOpts' => $this->serializeViewOpts($formsData['viewOpts'])
        ];


        return new ContactSerializeResult(200, $resultData);
    }

    protected function serializeFormItem(ContactFormEntity $formItem)
    {
        return $formItem->toArray();
    }

    protected function serializeFormInstance(Form $formInstance)
    {
        return $formInstance->toArray();
    }

    /**
     * @param array $viewOpts
     * @return array
     */
    protected function serializeViewOpts(array $viewOpts)
    {
        return $viewOpts;
    }

}
