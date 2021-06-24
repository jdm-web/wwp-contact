<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Plugin\Contact\Command\RgpdClearContactCommand;

class ContactCronService
{
    const TYPE = WWP_PLUGIN_CONTACT_NAME;

    public function getCronInventory(): array
    {
        $phpVersion = phpversion();
        $phpVersionFrags = explode('.',$phpVersion);
        return [
            'title'     => static::TYPE . ' Plugin',
            'inventory' => [
                [
                    'task'          => RgpdClearContactCommand::CommandName,
                    'command'       => 'WP_CLI_PHP=php'.implode('.',[$phpVersionFrags[0],$phpVersionFrags[1]]).' vendor/wp-cli/wp-cli/bin/wp '.RgpdClearContactCommand::CommandName,
                    'programmation' => 'chaque nuit à hh:mm',
                ],
            ],
        ];
    }
}
