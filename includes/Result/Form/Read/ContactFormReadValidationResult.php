<?php

namespace WonderWp\Plugin\Contact\Result\Form\Read;

use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

class ContactFormReadValidationResult extends AbstractRequestValidationResult
{
    const Success  = 'contact.form.read.validation.success';
    const NotFound = 'contact.form.read.validation.not_found';
    const Error    = 'contact.form.read.validation.error';

    /** @var ContactFormEntity */
    protected $form;

    /**
     * @return ContactFormEntity
     */
    public function getForm(): ContactFormEntity
    {
        return $this->form;
    }

    /**
     * @param ContactFormEntity $form
     * @return ContactFormReadValidationResult
     */
    public function setForm(ContactFormEntity $form): ContactFormReadValidationResult
    {
        $this->form = $form;
        return $this;
    }
}
