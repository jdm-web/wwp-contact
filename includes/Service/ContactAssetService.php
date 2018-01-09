<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 06/09/2016
 * Time: 18:59
 */

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Framework\Asset\AbstractAssetService;
use WonderWp\Framework\DependencyInjection\Container;

class ContactAssetService extends AbstractAssetService{

    public function getAssets()
    {
        if(empty($this->assets)) {
            $container = Container::getInstance();
            $manager = $container->offsetGet(WWP_PLUGIN_CONTACT_NAME.'.Manager');
            $pluginPath = $manager->getConfig('path.url');
            $assetClass = $container->offsetGet('wwp.assets.assetClass');
            $jsAssetGroup = (is_env_webpack()) ? 'plugins' : 'app';

            $this->assets = array(
                'css' => array(
                    new $assetClass('wwp-contact-admin', $pluginPath . '/admin/css/contact.scss', array('styleguide'), null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                    new $assetClass('wwp-contact', $pluginPath . '/public/css/contact.scss', array('styleguide'), null, false, $jsAssetGroup)
                )
                ,
                'js' => array(
                    new $assetClass('wwp-contact', $pluginPath . '/public/js/contact.js', array('app','styleguide'), null, false, $jsAssetGroup),
                    new $assetClass('wwp-contact-admin', $pluginPath . '/admin/js/contact.js', array(), null, false, AbstractAssetService::ADMIN_ASSETS_GROUP)
                )
            );
        }
        return $this->assets;
    }

}
