<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 06/09/2016
 * Time: 18:59
 */

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Asset\AbstractAssetService;
use WonderWp\Component\DependencyInjection\Container;

class ContactAssetService extends AbstractAssetService
{

    public function getAssets()
    {
        if (empty($this->assets)) {
            $manager = $this->manager;
            $pluginPath = $manager->getConfig('path.url');
            $assetClass = self::$assetClassName;

            if (is_env_webpack()) {
                $assetGroup = 'plugins';
                $this->assets = [
                    'css' => [
                        new $assetClass('wwp-contact-admin', $pluginPath . '/admin/css/contact.scss', ['styleguide'], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact', $pluginPath . '/public/css/contact.scss', ['styleguide'], null, false, $assetGroup),
                    ],
                    'js' => [
                        new $assetClass('wwp-contact-admin-imports', $pluginPath . '/admin/js/contact_admin_imports.js', [], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact-admin', $pluginPath . '/admin/js/contact.js', ['wwp-contact-admin-imports'], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact', $pluginPath . '/public/js/contact-es6.js', [], null, false, $assetGroup)
                    ]
                ];
            } else {
                $assetGroup = 'app';
                $this->assets = [
                    'css' => [
                        new $assetClass('wwp-contact-admin', $pluginPath . '/admin/css/contact.scss', ['styleguide'], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact', $pluginPath . '/public/css/contact.scss', ['styleguide'], null, false, $assetGroup),
                    ],
                    'js' => [
                        new $assetClass('wwp-contact-admin', $pluginPath . '/admin/js/contact.js', [], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact', $pluginPath . '/public/js/contact.js', ['app', 'styleguide'], null, false, $assetGroup)
                        ]
                    ];
            }

        }

        return $this->assets;
    }

}
