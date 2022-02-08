<?php

namespace WonderWp\Plugin\Contact\Exception;

use Exception;
use JsonSerializable;
use Throwable;

class ContactException extends Exception implements JsonSerializable
{
    /** @var array */
    protected $details;
    /** @var string */
    protected $domain;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $details = [])
    {
        $this->domain = WWP_CONTACT_TEXTDOMAIN;
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /** @inheritdoc */
    public function jsonSerialize()
    {
        $vars            = get_object_vars($this);
        $unnecessaryArgs = ['file', 'line', 'xdebug_message'];
        foreach ($unnecessaryArgs as $arg) {
            if (isset($vars[$arg])) {
                unset($vars[$arg]);
            }
        }
        $frags        = explode('\\', get_called_class());
        $vars['type'] = 'ER/' . end($frags);

        return $vars;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    public function toArray()
    {
        return json_decode(json_encode($this), true);
    }
}
