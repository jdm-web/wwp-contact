<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 16/05/2017
 * Time: 17:37
 */

namespace WonderWp\Plugin\Contact\Service\Exporter;

use WonderWp\Framework\API\Result;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Media\Medias;
use WonderWp\Framework\Service\AbstractService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;

class ContactCsvExporterService extends AbstractContactExporterService
{
    /**
     * @inheritdoc
     */
    public function export()
    {
        $export = [];

        if (!$this->formInstance instanceof ContactFormEntity) {
            return new Result(500, ['msg' => 'Given form is not a ContactFormEntity']);
        }

        $cols     = $this->getCols();
        $export[] = $cols;

        $records = $this->getRecords();

        if (empty($records)) {
            return new Result(500, ['msg' => 'no data to export found for given form']);
        }

        foreach ($records as $record) {
            /** @var ContactEntity $record */
            $row = [];
            foreach ($cols as $key => $trad) {
                $row[$key] = $this->getRecordVal($record, $key);
            }
            $export[] = $row;
        }

        $upDir = wp_upload_dir();
        $csv   = $this->format($export);
        $dest  = $upDir['basedir'] . '/contact/';
        $name  = 'export_csv_form' . $this->formInstance->getId() . '_' . date('Y_m_d_h_i') . '.csv';

        /** @var Container $container */
        $container = Container::getInstance();
        $fs        = $container['wwp.fileSystem'];
        $uploaded  = $fs->put_contents($dest . $name, $csv);

        if ($uploaded) {
            return new Result(200, ['file' => $upDir['baseurl'] . '/contact/' . $name]);
        } else {
            return new Result(500, ['msg' => 'Upload failed => ' . $dest . $name]);
        }
    }

    private function getCols()
    {
        $cols      = [
            'createdAt'=>__('createdAt.trad',WWP_CONTACT_TEXTDOMAIN)
        ];
        $em        = EntityManager::getInstance();
        $fieldRepo = $em->getRepository(ContactFormFieldEntity::class);
        $data      = json_decode($this->formInstance->getData(), true);
        if (!empty($data)) {
            foreach ($data as $fieldId => $fieldOptions) {
                $field = $fieldRepo->find($fieldId);
                if ($field instanceof ContactFormFieldEntity) {
                    $cols[$field->getName()] = __($field->getName() . '.trad', WWP_CONTACT_TEXTDOMAIN);
                }
            }
        }

        return $cols;
    }

    private function getRecordVal(ContactEntity $record, $key)
    {
        $val = method_exists($record, 'get' . ucfirst($key)) ? call_user_func([$record, 'get' . ucfirst($key)]) : $record->getData($key);

        if($val instanceof \DateTime){
            $val = $val->format('d/m/y');
        }

        return $val;
    }

    private function format(array $data)
    {

        //dump($data); return false;

        # Generate CSV data from array
        $fh = fopen('php://temp', 'rw'); # don't create a file, attempt
        # to use memory instead

        # write out the headers
        $headers = array_shift($data);
        fputcsv($fh, $headers);

        # write out the data
        foreach ($data as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        //encoding
        $csv = utf8_decode($csv);

        return $csv;
    }
}