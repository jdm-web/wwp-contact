<?php

namespace WonderWp\Plugin\Contact\Repository;

use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
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
            ->orderBy('m.createdAt','DESC')
        ;

        return $qb->getQuery()->getResult();
    }

     /**
      * @param string $mail
      * @return ContactEntity[]
      */
     public function findMessagesForEmailAndIds($email, $contactIds)
     {
       $qb = $this->createQueryBuilder('contact');
       $qb->where('contact.data LIKE :email')
          ->andWhere('contact.id IN(:ids)')
          ->setParameter(':email', '%' . $email . '%')
          ->setParameter('ids', array_values($contactIds))
       ;
       return $qb->getQuery()->getResult();
     }

     public function findMessagesForExport(ContactFormEntity $contactFormEntity){
         return $this->findBy([
             'form' => $contactFormEntity,
         ]);
     }
}
