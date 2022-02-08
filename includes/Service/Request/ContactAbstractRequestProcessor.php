<?php

namespace WonderWp\Plugin\Contact\Service\Request;

use WonderWp\Plugin\Contact\Exception\BadRequestException;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

abstract class ContactAbstractRequestProcessor
{
    /** @var string */
    public static $ResultClass = '';

    protected function isValidationResultInvalid(AbstractRequestValidationResult $validationResult)
    {
        return ($validationResult->getCode() !== 200);
    }

    protected function processingResultFromValidationResult(AbstractRequestValidationResult $validationResult)
    {
        $result = new static::$ResultClass(
            $validationResult->getCode(),
            $validationResult,
            $validationResult->getMsgKey(),
            $validationResult->getData(),
            $validationResult->getError()
        );
        return ($validationResult->getCode() === 200) ? $this->success($result) : $this->error($result);
    }

    /**
     * @param AbstractRequestProcessingResult $result
     * @return mixed
     */
    protected function error($result)
    {
        $filterName = '';
        if (defined($result::Error)) {
            $filterName = $result::Error;
        } elseif (str_contains($result->getMsgKey(), '.processing.error')) {
            $filterName = $result->getMsgKey();
        }

        if (empty($result->getError())) {
            $error = new BadRequestException($result->getMsgKey(), $result->getCode());
            $result->setError($error);
        }

        return !empty($filterName) ? apply_filters($filterName, $result) : $result;
    }

    /**
     * @param AbstractRequestProcessingResult $result
     * @return mixed
     */
    protected function success($result)
    {
        $filterName = '';
        if (!empty($result::Success)) {
            $filterName = $result::Success;
        } elseif (str_contains($result->getMsgKey(), '.processing.success')) {
            $filterName = $result->getMsgKey();
        }

        return !empty($filterName) ? apply_filters($filterName, $result) : $result;
    }
}
