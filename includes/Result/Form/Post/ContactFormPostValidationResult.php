<?php

namespace WonderWp\Plugin\Contact\Result\Form\Post;

use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

class ContactFormPostValidationResult extends AbstractRequestValidationResult
{
    const Success = 'contact.form.post.validation.success';
    const Error   = 'contact.form.post.validation.error';
}
