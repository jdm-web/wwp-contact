<?php

namespace WonderWp\Plugin\Contact\Service\Exporter;

use WonderWp\Component\HttpFoundation\Result;
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
