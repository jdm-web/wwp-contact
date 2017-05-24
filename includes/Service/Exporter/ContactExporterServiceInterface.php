<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 16/05/2017
 * Time: 17:39
 */

namespace WonderWp\Plugin\Contact\Service\Exporter;

use WonderWp\Framework\API\Result;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;

interface ContactExporterServiceInterface
{
    /**
     * @return ContactFormEntity
     */
    public function getFormInstance();

    /**
     * @param ContactFormEntity $formInstance
     *
     * @return static
     */
    public function setFormInstance($formInstance);

    /**
     * @return Result
     */
    public function export();
}
