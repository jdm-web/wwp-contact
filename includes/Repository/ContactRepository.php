<?php

namespace WonderWp\Plugin\Contact\Repository;

use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Core\Framework\Repository\BaseRepository;

class ContactRepository extends BaseRepository
{
    /**
     * @param string $mail
     *
     * @return ContactEntity[]
     */
    public function findMessagesFor($mail)
    {
        $qb = $this->createQueryBuilder('m');
        $qb
            ->where('m.data LIKE :mail')
            ->setParameter(':mail', '%' . $mail . '%')
        ;

        return $qb->getQuery()->getResult();
    }
}
