<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 16/05/2017
 * Time: 17:39
 */

namespace WonderWp\Plugin\Contact\Service\Exporter;

use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;

abstract class AbstractContactExporterService implements ContactExporterServiceInterface
{
    /** @var  ContactFormEntity */
    protected $formInstance;

    private $records;

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

    public function getRecords(){
        if(empty($this->records)){
            /** @var EntityManager $em */
            $em = EntityManager::getInstance();
            $repo = $em->getRepository(ContactEntity::class);
            $this->records = $repo->findBy([
                'form'=>$this->formInstance
            ]);
        }
        return $this->records;
    }



}
