<?php

/**
 * Fired during plugin activation
 *
 * @link       http://digital.wonderful.fr
 * @since      1.0.0
 *
 * @package    Wonderwp
 * @subpackage Wonderwp/includes
 */

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Framework\Form\Field\CheckBoxField;
use WonderWp\Framework\Form\Field\EmailField;
use WonderWp\Framework\Form\Field\InputField;
use WonderWp\Framework\Form\Field\SelectField;
use WonderWp\Framework\Form\Field\TextAreaField;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractDoctrinePluginActivator;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 * Mainly the table creation if need be
 *
 * @since      1.0.0
 * @package    Wonderwp
 * @subpackage Wonderwp/includes
 * @author     Wonderful <jeremy.desvaux@wonderful.fr>
 */
class ContactActivator extends AbstractDoctrinePluginActivator
{
    /**
     * Create table for entity
     */
    public function activate()
    {
        $this->createTables([ContactFormFieldEntity::class,ContactFormEntity::class, ContactEntity::class]);

        $this->insertData(ContactFormFieldEntity::class, '1.0.0', [
            new ContactFormFieldEntity(InputField::class, ['name' => 'nom']),
            new ContactFormFieldEntity(InputField::class, ['name' => 'prenom']),
            new ContactFormFieldEntity(EmailField::class, ['name' => 'mail']),
            new ContactFormFieldEntity(InputField::class, ['name' => 'telephone']),
            new ContactFormFieldEntity(SelectField::class, ['name' => 'sujet']),
            new ContactFormFieldEntity(TextAreaField::class, ['name' => 'message']),
            new ContactFormFieldEntity(CheckBoxField::class, ['name' => 'rgpd']),
        ]);

        $this->setupOverride([
            'child_namespace' => 'WonderWp\Plugin\Contact\Child',
            'parent_manager_use' => 'WonderWp\Plugin\Contact\ContactManager',
            'child_manager_class_name' => 'ContactThemeManager',
            'parent_manager_class_name' => 'ContactManager',
            'psr4_namespace' => "WonderWp\\Plugin\\Contact\\Child\\",
            'override_dir'   => "web/app/themes/".str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) )."/plugins/wwp-contact",
            'manager_constant_name' => 'WWP_PLUGIN_CONTACT_MANAGER'
        ]);

        $this->copyLanguageFiles(dirname(__DIR__) . '/languages');
    }
}
