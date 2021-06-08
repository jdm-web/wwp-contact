<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Asset\AbstractAssetService;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\ChildAwareAssetsServiceTrait;

class ContactAssetService extends AbstractAssetService
{
    use ChildAwareAssetsServiceTrait;

    public function getAssets()
    {
        if (empty($this->assets)) {
            $manager = $this->manager;
            $pluginPath = $manager->getConfig('path.url');
            $pluginRoot = $manager->getConfig('path.root');
            $assetClass = self::$assetClassName;
            $assetGroup = $manager->getConfig('jsAssetGroup');
            $stylesheetsToLoad = $this->getStylesheetsPaths($pluginRoot, $pluginPath, $manager->getConfig('stylesheetToLoad'));
            $pluginSlug = WWP_PLUGIN_CONTACT_NAME;

            $cssAssets = [];

            if (!empty($stylesheetsToLoad)) {
                $cssAssets = $this->registerStylesheets($stylesheetsToLoad, $pluginSlug, $assetClass, $assetGroup);
            }

            $cssAssets[] = new $assetClass('wwp-contact-admin', $pluginPath . '/admin/css/contact.scss', ['styleguide'], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP);

            if (is_env_webpack()) {
                $this->assets = [
                    'css' => $cssAssets,
                    'js' => [
                        new $assetClass('wwp-contact-admin-imports', $pluginPath . '/admin/js/contact_admin_imports.js', [], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact-admin', $pluginPath . '/admin/js/contact.js', ['wwp-contact-admin-imports'], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact-form-admin', $pluginPath . '/admin/js/contact_form.js', ['wwp-contact-admin-imports'], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact', $pluginPath . '/public/js/contact-es6.js', [], null, false, $assetGroup)
                    ]
                ];
            } else {
                $this->assets = [
                    'css' => $cssAssets,
                    'js' => [
                        new $assetClass('wwp-contact-admin', $pluginPath . '/admin/js/contact.js', [], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact-form-admin', $pluginPath . '/admin/js/contact_form.js', [], null, false, AbstractAssetService::ADMIN_ASSETS_GROUP),
                        new $assetClass('wwp-contact', $pluginPath . '/public/js/contact.js', ['app', 'styleguide'], null, false, $assetGroup)
                        ]
                    ];
            }

        }

        return $this->assets;
    }

}
