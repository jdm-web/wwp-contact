<?php

namespace WonderWp\Plugin\Contact\Result\AbstractResult;

use WonderWp\Plugin\Contact\Exception\ContactException;

abstract class AbstractRequestProcessingResult extends AbstractContactResult
{
    /** @var AbstractRequestValidationResult */
    protected $validationResult;

    /**
     * @param int $code
     * @param AbstractRequestValidationResult $validationResult
     * @param string $msgKey
     * @param array $data
     * @param ContactException|null $error
     */
    public function __construct($code, AbstractRequestValidationResult $validationResult, string $msgKey, array $data = [], ContactException $error = null)
    {
        parent::__construct($code, $msgKey, $data, $error);
        $this->validationResult = $validationResult;
    }

    /**
     * @return AbstractRequestValidationResult
     */
    public function getValidationResult(): AbstractRequestValidationResult
    {
        return $this->validationResult;
    }

}
