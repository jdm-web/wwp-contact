<?php

namespace WonderWp\Plugin\Contact\Result\Form\Post;

use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

class ContactFormPostValidationResult extends AbstractRequestValidationResult
{
    const Success  = 'contact.form.post.validation.success';
    const NotFound = 'contact.form.post.validation.not_found';
    const Error    = 'contact.form.post.validation.error';

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
     * @return ContactFormPostValidationResult
     */
    public function setForm(ContactFormEntity $form): ContactFormPostValidationResult
    {
        $this->form = $form;
        return $this;
    }
}