<?php

namespace WonderWp\Plugin\Contact\Service;

class ContactTestDetector
{
    public static function isUnitTesting(): bool
    {
        return defined('RUNNING_PHP_UNIT_TESTS');
    }

    public static function isIntegrationTesting(string $origin): bool
    {
        return in_array($origin, ['postman', 'cypress']);
    }
}
