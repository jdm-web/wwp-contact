<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 15/09/2016
 * Time: 15:40
 */

namespace WonderWp\Plugin\Contact;


use WonderWp\APlugin\AbstractManager;
use WonderWp\DI\Container;
use WonderWp\Route\AbstractRouteService;

class ContactRouteService extends AbstractRouteService
{
    public function getRoutes(){
        if(empty($this->_routes)) {
            $manager = Container::getInstance()->offsetGet('wwp-contact.Manager');
            $this->_routes = array(
                ['contactFormSubmit',array($manager->getController(AbstractManager::$PUBLICCONTROLLERTYPE),'handleFormAction'),'POST']
            );
        }

        return $this->_routes;
    }
}
