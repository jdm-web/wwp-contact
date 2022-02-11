<?php

namespace WonderWp\Plugin\Contact\Result\Form\Post;

use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;

class ContactFormPostProcessingResult extends AbstractRequestProcessingResult
{
    const Success     = 'contact.form.post.processing.success';
    const Error       = 'contact.form.post.processing.error';
    const FakeSuccess = 'contact.form.post.processing._success'; //For bots

    /** @var ContactEntity */
    protected $contactEntity;

    /**
     * @return ContactEntity
     */
    public function getContactEntity(): ContactEntity
    {
        return $this->contactEntity;
    }

    /**
     * @param ContactEntity $contactEntity
     * @return ContactFormPostProcessingResult
     */
    public function setContactEntity(ContactEntity $contactEntity): ContactFormPostProcessingResult
    {
        $this->contactEntity = $contactEntity;
        return $this;
    }
}
