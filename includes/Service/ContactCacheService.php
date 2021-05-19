<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Plugin\Core\Cache\AbstractCacheService;

class ContactCacheService extends AbstractCacheService
{
    const TYPE = WWP_PLUGIN_CONTACT_NAME;

    private array $entities;

    public function __construct(array $entities)
    {
        $this->entities = $entities;
    }

    protected function getConcernedTypes(): array
    {
        return [...$this->entities, static::TYPE];
    }

    public function getCacheInventory(): array
    {
        return [
            'title'     => 'Contact Plugin',
            'inventory' => [
                ['title' => 'Cache busting strategy', 'value' => '<strong class="keyword">When</strong> an entity of type ' . implode(",", $this->getConcernedTypes()) . '<br /><strong class="keyword">is</strong> added / modified / delete,<br /><strong class="keyword">then</strong> the pages where the following shortcode is present are busted (' . $this->getShortcodePattern() . ').'],
                ['title' => 'Excluded Urls', 'value' => 'None'],
            ],
        ];
    }
}
