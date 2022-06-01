<?php

namespace WonderWp\Plugin\Contact\Result\AbstractResult;

use WonderWp\Plugin\Contact\Exception\ContactException;

abstract class AbstractRequestValidationResult extends AbstractContactResult
{
    /** @var array */
    protected $requestData;

    /**
     * @param int $code
     * @param array $requestData
     * @param string $msgKey
     * @param array $data
     * @param ContactException|null $error
     */
    public function __construct($code, array $requestData, string $msgKey = '', array $data = [], ContactException $error = null)
    {
        parent::__construct($code, $msgKey, $data, $error);
        $this->requestData = $requestData;
    }

    /**
     * @param string $key
     * @return array|mixed|null
     */
    public function getRequestData($key = '', $default = null)
    {
        if (!empty($key)) {
            return isset($this->requestData[$key]) ? $this->requestData[$key] : $default;
        } else {
            return $this->requestData;
        }
    }

    /**
     * @param array $requestData
     * @return AbstractRequestValidationResult
     */
    public function setRequestData(array $requestData): AbstractRequestValidationResult
    {
        $this->requestData = $requestData;
        return $this;
    }
}
