<?php

namespace WonderWp\Plugin\Contact\Exception;

class ClassNotFoundException extends ContactException
{
    public function __construct(string $class, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Class "%s" not found.', $class), 0, $previous);
    }
}
