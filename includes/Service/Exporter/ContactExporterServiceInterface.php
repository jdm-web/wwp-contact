<?php

namespace WonderWp\Plugin\Contact\Service\Exporter;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;

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
     * @param array $records
     *
     * @return Result
     */
    public function export(array $records, ContactFormFieldRepository $repository);
}
