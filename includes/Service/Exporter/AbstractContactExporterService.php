<?php

namespace WonderWp\Plugin\Contact\Service\Exporter;

use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;

abstract class AbstractContactExporterService implements ContactExporterServiceInterface
{
    /** @var  ContactFormEntity */
    protected $formInstance;

    /**
     * @inheritdoc
     */
    public function getFormInstance()
    {
        return $this->formInstance;
    }

    /**
     * @inheritdoc
     */
    public function setFormInstance($formInstance)
    {
        $this->formInstance = $formInstance;

        return $this;
    }

}
