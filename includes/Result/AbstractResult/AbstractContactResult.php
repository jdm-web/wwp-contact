<?php

namespace WonderWp\Plugin\Contact\Result\AbstractResult;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Plugin\Contact\Exception\ContactException;

abstract class AbstractContactResult extends Result
{
    /** @var ContactException */
    protected $error;

    /** @var string */
    protected $msgKey;

    /**
     * @param int $code
     * @param string $msgKey
     * @param array $data
     * @param ContactException|null $error
     */
    public function __construct($code, string $msgKey, array $data = [], ContactException $error = null)
    {
        parent::__construct($code, $data);
        $this->error  = $error;
        $this->msgKey = $msgKey;
    }

    /**
     * @return ContactException|null
     */
    public function getError(): ?ContactException
    {
        return $this->error;
    }

    /**
     * @param ContactException|null $error
     * @return AbstractContactResult
     */
    public function setError(?ContactException $error): AbstractContactResult
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return string
     */
    public function getMsgKey(): string
    {
        return $this->msgKey;
    }

    /**
     * @param string $msgKey
     * @return AbstractContactResult
     */
    public function setMsgKey(string $msgKey): AbstractContactResult
    {
        $this->msgKey = $msgKey;
        return $this;
    }

    public function jsonSerialize()
    {
        $jsonSerialize = parent::jsonSerialize();

        if (!empty($this->msgKey)) {
            $jsonSerialize['message'] = [
                'key'        => $this->msgKey,
                'domain'     => WWP_CONTACT_TEXTDOMAIN,
                'translated' => trad($this->msgKey, WWP_CONTACT_TEXTDOMAIN)
            ];
            if (isset($jsonSerialize['msgKey'])) {
                unset($jsonSerialize['msgKey']);
            }
        }

        return $jsonSerialize;
    }


}
