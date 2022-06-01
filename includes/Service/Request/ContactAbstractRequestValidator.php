<?php

namespace WonderWp\Plugin\Contact\Service\Request;

use WonderWp\Plugin\Contact\Exception\BadRequestException;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;

abstract class ContactAbstractRequestValidator implements ContactRequestValidatorInterface
{
    /** @var string */
    public static $ResultClass = '';

    public function checkRequiredParameters(array $requiredParameterNames, array $requestData)
    {
        $errors = [];
        if (!empty($requiredParameterNames)) {
            foreach ($requiredParameterNames as $requiredParameterName) {
                if (empty($requestData[$requiredParameterName])) {
                    $errors[$requiredParameterName] = [$requiredParameterName . ' is missing'];
                }
            }
        }
        return $errors;
    }

    public function requiredParametersValidationResult(array $requestData, array $errors)
    {
        $resultClass = static::$ResultClass;
        $msgKey      = $resultClass::Error;

        $result = new $resultClass(
            400,
            $requestData,
            $msgKey
        );
        $error  = new BadRequestException($result->getMsgKey(), $result->getCode(), null, $errors);
        $result->setError($error);

        return $this->error($result);
    }

    /**
     * @param AbstractRequestValidationResult $result
     * @return mixed
     */
    protected function error($result)
    {
        $filterName = '';
        if (defined($result::Error)) {
            $filterName = $result::Error;
        } elseif (str_contains($result->getMsgKey(), '.validation.error')) {
            $filterName = $result->getMsgKey();
        }

        if (empty($result->getError())) {
            $error = new BadRequestException($result->getMsgKey(), $result->getCode());
            $result->setError($error);
        }

        return !empty($filterName) ? apply_filters($filterName, $result) : $result;
    }

    /**
     * @param AbstractRequestValidationResult $result
     * @return mixed
     */
    protected function success($result)
    {
        $filterName = '';
        if (defined($result::Success)) {
            $filterName = $result::Success;
        } elseif (str_contains($result->getMsgKey(), '.validation.success')) {
            $filterName = $result->getMsgKey();
        }
        return !empty($filterName) ? apply_filters($filterName, $result) : $result;
    }
}
