<?php

namespace WonderWp\Plugin\Contact\Result\Form\Read;

use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;

class ContactFormReadProcessingResult extends AbstractRequestProcessingResult
{
    const Success = 'contact.form.read.processing.success';
    const Error   = 'contact.form.read.processing.error';

    /** @var ContactFormEntity */
    protected $form;

    /** @var array */
    protected $serializedForm = [];

    /**
     * @return ContactFormEntity
     */
    public function getForm(): ContactFormEntity
    {
        return $this->form;
    }

    /**
     * @param ContactFormEntity $form
     * @return ContactFormReadProcessingResult
     */
    public function setForm(ContactFormEntity $form): ContactFormReadProcessingResult
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return array
     */
    public function getSerializedForm(): array
    {
        return $this->serializedForm;
    }

    /**
     * @param array $serializedForm
     * @return ContactFormReadProcessingResult
     */
    public function setSerializedForm(array $serializedForm): ContactFormReadProcessingResult
    {
        $this->serializedForm = $serializedForm;
        return $this;
    }
}
