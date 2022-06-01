<?php

namespace WonderWp\Plugin\Contact\Service\Serializer;

use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\ContactSerializeResult;

interface ContactSerializerInterface
{
    public function serialize(ContactFormEntity $contactFormEntity): ContactSerializeResult;

    public function unserialize(ContactFormEntity $contactFormEntity): ContactSerializeResult;
}
