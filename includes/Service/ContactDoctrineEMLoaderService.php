<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Plugin\Core\Framework\Doctrine\AbstractDoctrineEMLoaderService;

class ContactDoctrineEMLoaderService extends AbstractDoctrineEMLoaderService
{
    /**
     * @return static
     */
    public function register()
    {
        return $this->registerEntityPath(implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'Entity']));
    }
}
