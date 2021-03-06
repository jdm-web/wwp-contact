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


use Exception;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\EmailField;
use WonderWp\Component\Form\Field\InputField;
use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Component\Form\Field\TextAreaField;
use WonderWp\Component\Form\Field\CheckBoxField;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractDoctrinePluginActivator;
use WP_Filesystem_Base;

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
     * @throws Exception
     */
    public function activate()
    {
        $this->createTables([ContactFormFieldEntity::class,ContactFormEntity::class, ContactEntity::class]);

        $this->insertData(ContactFormFieldEntity::class, '1.0.0', [
            new ContactFormFieldEntity(InputField::class, ['name' => 'nom','autocomplete'=>'family-name']),
            new ContactFormFieldEntity(InputField::class, ['name' => 'prenom','autocomplete'=>'given-name']),
            new ContactFormFieldEntity(EmailField::class, ['name' => 'mail','autocomplete'=>'email']),
            new ContactFormFieldEntity(InputField::class, ['name' => 'telephone','autocomplete'=>'tel']),
            new ContactFormFieldEntity(SelectField::class, ['name' => 'sujet']),
            new ContactFormFieldEntity(TextAreaField::class, ['name' => 'message']),
            new ContactFormFieldEntity(CheckBoxField::class, ['name' => 'rgpd-consent']),
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

        $this->copyLanguageFiles(dirname(dirname(__DIR__)) . '/languages');
        $this->createExportFolder();
        $this->copyTestSuites([
            'phpunit'=>dirname(dirname(__DIR__)) . '/tests/phpunit/wwp-contact-phpunit-tests.sh',
            'cypress'=>dirname(dirname(__DIR__)) . '/tests/cypress/wwp-contact-spec.js',
        ]);
    }

    /**
     * @throws Exception
     */
    protected function createExportFolder(){
        /** @var WP_Filesystem_Base $fileSystem */
        $fileSystem = Container::getInstance()['wwp.fileSystem'];
        $uploadDirInfo = wp_upload_dir();
        $exportFolder = $uploadDirInfo['basedir'].'/contact';
        if (!is_dir($exportFolder)) {
            if (!$fileSystem->mkdir($exportFolder, 0777)) {
                throw new Exception('Required folder creation failed: ' . $exportFolder);
            }
        }
    }
}
