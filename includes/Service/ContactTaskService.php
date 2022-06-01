<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Task\TaskServiceInterface;
use WonderWp\Plugin\Contact\Command\RgpdClearContactCommand;

/**
 * CLI commands
 */
class ContactTaskService implements TaskServiceInterface
{
    /** @inheritdoc */
    public function register()
    {
        if (!class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command(RgpdClearContactCommand::CommandName, RgpdClearContactCommand::Class);
    }
}
