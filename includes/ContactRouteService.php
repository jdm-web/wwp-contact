<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 15/09/2016
 * Time: 15:40
 */

namespace WonderWp\Plugin\Contact;


use WonderWp\Framework\AbstractPlugin\AbstractManager;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Route\AbstractRouteService;

class ContactRouteService extends AbstractRouteService
{
    public function getRoutes(){
        if(empty($this->_routes)) {
            $manager = Container::getInstance()->offsetGet('wwp-contact.Manager');
            $this->_routes = array(
                ['contactFormSubmit',array($manager->getController(AbstractManager::PUBLIC_CONTROLLER_TYPE),'handleFormAction'),'POST']
            );
        }

        return $this->_routes;
    }
}
