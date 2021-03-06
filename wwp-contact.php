<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://digital.wonderful.fr
 * @since             1.0.0
 * @package           WonderWp
 *
 * @wordpress-plugin
 * Plugin Name:       wwp Contact
 * Plugin URI:        http://digital.wonderful.fr/wonderwp/wwp-contact
 * Description:       Gestion de formulaires de contact et sauvegarde des messages générés
 * Version:           1.2.1
 * Author:            Wonderful
 * Author URI:        https://wonderful.fr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wwp-contact
 * Domain Path:       /languages
 */

use WonderWp\Component\PluginSkeleton\Service\ActivatorInterface;
use WonderWp\Component\PluginSkeleton\Service\DeactivatorInterface;
use WonderWp\Component\PluginSkeleton\ManagerInterface;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Service\ServiceInterface;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include __DIR__.'/wwp-contact-constants.php';

/**
 * Register activation hook
 * The code that runs during plugin activation.
 * This action is documented in includes/ErActivator.php
 */
register_activation_hook(__FILE__, function () {
    $activator = Container::getInstance()->offsetGet(WWP_PLUGIN_CONTACT_NAME . '.Manager')->getService(ServiceInterface::ACTIVATOR_NAME);

    if ($activator instanceof ActivatorInterface) {
        $activator->activate();
    }
});

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 * This class is called the manager
 * Instanciate here because it handles autoloading
 */
$plugin = WWP_PLUGIN_CONTACT_MANAGER;
$plugin = new $plugin(WWP_PLUGIN_CONTACT_NAME, WWP_PLUGIN_CONTACT_VERSION);

if (!$plugin instanceof ManagerInterface) {
    throw new \BadMethodCallException(sprintf('Invalid manager class for %s plugin : %s', WWP_PLUGIN_CONTACT_NAME, WWP_PLUGIN_CONTACT_MANAGER));
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
$plugin->run();
