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

namespace WonderWp\Plugin\Contact;

use Doctrine\ORM\EntityManager;
use WonderWp\APlugin\AbstractPluginActivator;
use WonderWp\DI\Container;
use WonderWp\Forms\Fields\EmailField;
use WonderWp\Forms\Fields\InputField;
use WonderWp\Forms\Fields\SelectField;
use WonderWp\Forms\Fields\TextAreaField;

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
class ContactActivator extends AbstractPluginActivator
{
    /**
     * Create table for entity
     */
    public function activate()
    {
        $this->_createTable(ContactFormFieldEntity::class);
        $this->_createTable(ContactFormEntity::class);
        $this->_createTable(ContactEntity::class);

        $this->_insertData(ContactFormFieldEntity::class, '1.0.0', [
            new ContactFormFieldEntity(InputField::class, ['name' => 'nom']),
            new ContactFormFieldEntity(InputField::class, ['name' => 'prenom']),
            new ContactFormFieldEntity(EmailField::class, ['name' => 'mail']),
            new ContactFormFieldEntity(InputField::class, ['name' => 'telephone']),
            new ContactFormFieldEntity(SelectField::class, ['name' => 'sujet']),
            new ContactFormFieldEntity(TextAreaField::class, ['name' => 'message']),
        ]);
    }

    /**
     * @param string $entityName
     * @param string $version
     * @param array  $data
     */
    protected function _insertData($entityName, $version, array $data)
    {
        if (version_compare($version, $this->_version) >= 0) {
            $installed_ver = get_option($entityName . '_data_version');

            if (version_compare($version, $installed_ver) > 0) {
                $container = Container::getInstance();
                /** @var EntityManager $em */
                $em = $container->offsetGet('entityManager');
                foreach ($data as $d) {
                    $em->persist($d);
                }
                $em->flush();

                update_option($entityName . "_data_version", $version);
            }
        }
    }
}
