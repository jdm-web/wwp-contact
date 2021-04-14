<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Service\AbstractService;

class ContactCacheService extends AbstractService
{
    protected function getConcernedEntities()
    {
        $contactFormEntityName  = $this->manager->getConfig('entityName');
        $contactEntityName      = $this->manager->getConfig('contactEntityName');
        $contactFieldEntityName = $this->manager->getConfig('contactFormFieldEntityName');

        return [$contactEntityName, $contactFieldEntityName, $contactFormEntityName];
    }

    public function isEntityNameConcerned($entityName)
    {
        return in_array($entityName, $this->getConcernedEntities());
    }

    public function getShortcodePattern()
    {
        return "[wwpmodule slug=\'" . WWP_PLUGIN_CONTACT_NAME . "\'";
    }

    public function getCacheInventory()
    {
        return [
            'title'     => 'Contact Plugin',
            'inventory' => [
                ['title' => 'Concerned Entities', 'value' => implode("<br />", $this->getConcernedEntities())],
                ['title' => 'Shortcode Pattern', 'value' => highlight_string(print_r($this->getShortcodePattern(), true), true)],
                ['title' => 'Excluded Urls', 'value' => 'None'],
            ],
        ];
    }
}
