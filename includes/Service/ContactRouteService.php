<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\Routing\Route\AbstractRouteService;

class ContactRouteService extends AbstractRouteService
{
    public function getRoutes()
    {
        if (empty($this->_routes)) {
            $this->_routes = [
                ['contactFormSubmit', [$this->manager->getController(AbstractManager::PUBLIC_CONTROLLER_TYPE), 'handleFormAction'], 'POST'],
            ];
        }

        return $this->_routes;
    }
}
