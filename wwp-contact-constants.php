<?php
use WonderWp\Plugin\Contact\ContactManager;

define('WWP_PLUGIN_CONTACT_NAME','wwp-contact');
define('WWP_PLUGIN_CONTACT_VERSION','1.0.0');
define('WWP_CONTACT_TEXTDOMAIN','wwp-contact');
if (!defined('WWP_PLUGIN_CONTACT_MANAGER')) {
    define('WWP_PLUGIN_CONTACT_MANAGER', ContactManager::class);
}
