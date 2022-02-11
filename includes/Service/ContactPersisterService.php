<?php

namespace WonderWp\Plugin\Contact\Service;

use Doctrine\ORM\Exception\ORMException;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Media\Medias;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;

class ContactPersisterService extends AbstractService
{

    /**
     * @param ContactEntity $contactEntity
     *
     * @return Result
     */
    public function persistContactEntity(ContactEntity $contactEntity)
    {
        $em = EntityManager::getInstance();

        try {
            $em->persist($contactEntity);
            $em->flush();

            return new Result(200, ['msg' => 'Entity persisted']);

        } catch (ORMException $e) {
            return new Result(500, ['msg' => 'Entity not persisted', 'exception' => $e->getMessage()]);
        }
    }

    public function persistMedia($file,$dest,$fileName){
        return Medias::uploadTo($file, $dest, $fileName);
    }

}
