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
                ['title' => 'Cache busting strategy', 'value' => '<strong class="keyword">When</strong> an entity of type '.implode(",", $this->getConcernedEntities()).'<br /><strong class="keyword">is</strong> added / modified / delete,<br /><strong class="keyword">then</strong> the pages where the following shortcode is present are busted ('.$this->getShortcodePattern().').'],
                ['title' => 'Excluded Urls', 'value' => 'None'],
            ],
        ];
    }
}
