<?php

namespace WonderWp\Plugin\Contact\Result\Form\Post;

use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;

class ContactFormPostProcessingResult extends AbstractRequestProcessingResult
{
    const Success     = 'contact.form.post.processing.success';
    const Error       = 'contact.form.post.processing.error';
    const FakeSuccess = 'contact.form.post.processing._success'; //For bots
}
