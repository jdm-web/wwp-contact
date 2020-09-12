<?php
namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Task\TaskServiceInterface;
use WonderWp\Plugin\Contact\Service\Tasks\Rgpd;

/**
 * High Co CLI commands
 */
class ContactTaskService implements TaskServiceInterface
{
    /** @inheritdoc */
    public function register()
    {
        if (!class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('rgpd-clear-contact', Rgpd::Class);
    }
}
