<?php

namespace WonderWp\Plugin\Contact\Test\PhpUnit\Service;

use PHPUnit\Framework\TestCase;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Service\Exporter\ContactCsvExporterService;

class ContactCsvExporterServiceTest extends TestCase
{
    static $managerClass  = WWP_PLUGIN_CONTACT_MANAGER;
    static $pluginName    = WWP_PLUGIN_CONTACT_NAME;
    static $pluginVersion = WWP_PLUGIN_CONTACT_VERSION;

    /** @var ContactManager */
    protected $manager;

    /** @var ContactCsvExporterService */
    protected $service;

    public function setUp(): void
    {
        $managerClass  = static::$managerClass;
        $this->manager = new $managerClass(static::$pluginName, static::$pluginVersion);
        $this->service = $this->manager->getService('exporter');
    }

    public function test_export_folder_should_be_writable()
    {
        $uplodDirMock     = ['basedir' => WP_CONTENT_DIR . '/uploads/'];
        $exportFolderPath = $this->service->getExportPath($uplodDirMock);
        $isFolderWritable = is_writable($exportFolderPath);
        $this->assertTrue($isFolderWritable, "ContactCsvExporterService export path is not writable, therefore contact CSV exports won't work.");
    }
}
