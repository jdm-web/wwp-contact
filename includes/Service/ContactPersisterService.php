<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;

class ContactPersisterService extends AbstractService
{

    /**
     * @param ContactEntity $contactEntity
     *
     * @return $this
     */
    public function persistContactEntity(ContactEntity $contactEntity){
        $container = Container::getInstance();
        /** @var EntityManager $em */
        $em = $container->offsetGet('entityManager');

        $em->persist($contactEntity);
        $em->flush();

        return $this;
    }

}
