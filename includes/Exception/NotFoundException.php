<?php

namespace WonderWp\Plugin\Contact\Exception;

use Throwable;

class NotFoundException extends ContactException
{
    public function __construct($message = "", $code = 404, Throwable $previous = null, array $details = [])
    {
        if (empty($message)) {
            $message = 'not.found';
        }
        parent::__construct($message, $code, $previous, $details);
    }
}
