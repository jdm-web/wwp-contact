<?php

namespace WonderWp\Plugin\Contact\Service\Tasks;

use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Log\DirectOutputLogger;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;

/**
 * Task remove all ContactEntity having created_at over ContactFormEntity->numberOfDaysBeforeRemove.
 */
class Rgpd
{
    /**
     * @var DirectOutputLogger
     */
    private $log;

    /**
     * Launch commande.
     *
     * @param array $args
     * @param array $assocArgs
     */
    public function __invoke($args, $assocArgs)
    {
        // set time limit
        set_time_limit(0);

        $container = Container::getInstance();
        $this->log = new DirectOutputLogger();

        // Start
        $this->log('====================================================');
        $this->log('========= WWP CONTACT - RGPD CLEAR CONTACT =========');
        $this->log('====================================================');
        $this->log('');

        // Request
        $em = $container->offsetGet('entityManager');
        $contactEntities = $em->getRepository(ContactEntity::class)->createQueryBuilder('contact')
          ->innerJoin('contact.form', 'contact_form')
          ->where('contact_form.numberOfDaysBeforeRemove IS NOT NULL')
          ->andWhere("CURRENT_DATE() >= DATE_ADD(contact.createdAt, contact_form.numberOfDaysBeforeRemove, 'day')")
          ->getQuery()->getResult()
        ;

        // Remove them
        $contactManager = $container->offsetGet(WWP_PLUGIN_CONTACT_NAME . '.Manager');
        $deleterService = $contactManager->getService('userDeleter');
        $deleterService->removeContactEntities($contactEntities);

        // Message removes
        $this->log('Number of ContactEntity removed: '.print_r(count($contactEntities), true));

        // Check numberOfDaysBeforeRemove is not null
        $contactFormErrors = $em->getRepository(ContactFormEntity::class)->createQueryBuilder('contact_form')
          ->where('contact_form.numberOfDaysBeforeRemove IS NULL')
          ->getQuery()->getResult()
        ;
        $this->log('');
        $this->log('ContactForm withtout DGPD delay (numberOfDaysBeforeRemove): ');
        if (0 == count($contactFormErrors)) {
            $this->log('- all ContactForm are completed');
        } else {
            foreach ($contactFormErrors as $contactFormError) {
                $this->log('- ContactForm with id: '.print_r($contactFormError->getId(), true));
            }
        }
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->log->log('', $message);
    }
}
