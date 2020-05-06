<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Service\AbstractService;

class ContactCacheService extends AbstractService
{
    public function isEntityNameConcerned($entityName)
    {
        $contactFormEntityName  = $this->manager->getConfig('entityName');
        $contactEntityName      = $this->manager->getConfig('contactEntityName');
        $contactFieldEntityName = $this->manager->getConfig('contactFormFieldEntityName');

        return in_array($entityName, [$contactEntityName, $contactFieldEntityName, $contactFormEntityName]);
    }

    public function getShortcodePattern()
    {
        return "[wwpmodule slug=\'".WWP_PLUGIN_CONTACT_NAME."\'";
    }
}
