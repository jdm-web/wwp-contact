<?php

namespace WonderWp\Plugin\Contact\Service\Tasks;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Logging\DirectOutputLogger;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Service\ContactUserDeleterService;

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
        $this->log('Date and time : '.date('Y-m-d H:i:s'));

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
        /** @var ContactUserDeleterService $deleterService */
        $deleterService = $contactManager->getService('userDeleter');
        $deleterService->removeContactEntities($contactEntities);

        // Message removes
        $this->log('Number of ContactEntity removed: '.print_r(count($contactEntities), true));

        // Check numberOfDaysBeforeRemove is not null
        /** @var ContactEntity[] $contactFormErrors */
        $contactFormErrors = $em->getRepository(ContactFormEntity::class)->createQueryBuilder('contact_form')
          ->where('contact_form.numberOfDaysBeforeRemove IS NULL')
          ->getQuery()->getResult()
        ;
        $this->log('');
        $this->log('ContactForm withtout DGPD delay (numberOfDaysBeforeRemove): '.(int)count($contactFormErrors));
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
