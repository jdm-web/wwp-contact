<?php

namespace WonderWp\Plugin\Contact\Command;

use Exception;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Logging\DirectOutputLogger;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\ClearContactResult;
use WonderWp\Plugin\Contact\Service\ContactUserDeleterService;

/**
 * Task remove all ContactEntity having created_at over ContactFormEntity->numberOfDaysBeforeRemove.
 */
class RgpdClearContactCommand
{
    const CommandName = 'rgpd-clear-contact';

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

        $container       = Container::getInstance();
        $this->log       = new DirectOutputLogger();
        $resData         = [];
        $resData['date'] = date('Y-m-d H:i:s');
        $start           = microtime(true);

        // Start
        $this->log('====================================================');
        $this->log('========= WWP CONTACT - RGPD CLEAR CONTACT =========');
        $this->log('====================================================');
        $this->log('');
        $this->log('Date and time : ' . $resData['date']);

        try {

            // Request
            $em              = $container->offsetGet('entityManager');
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
            $resData['removed'] = 'Number of ContactEntity removed: ' . print_r(count($contactEntities), true);
            $this->log($resData['removed']);

            // Check numberOfDaysBeforeRemove is not null
            /** @var ContactEntity[] $contactFormErrors */
            $contactFormErrors = $em->getRepository(ContactFormEntity::class)->createQueryBuilder('contact_form')
                                    ->where('contact_form.numberOfDaysBeforeRemove IS NULL')
                                    ->getQuery()->getResult()
            ;
            $this->log('');
            $resData['noRgpdDelay'] = 'ContactForm without RGPD delay (numberOfDaysBeforeRemove): ' . (int)count($contactFormErrors);
            $this->log($resData['noRgpdDelay']);
            if (0 == count($contactFormErrors)) {
                $suffix = '- all ContactForm are completed';
                $this->log($suffix);
            } else {
                $suffix = '';
                foreach ($contactFormErrors as $contactFormError) {
                    $thisSuffix = '- ContactForm with id: ' . print_r($contactFormError->getId(), true);
                    $this->log($thisSuffix);
                    $suffix .= $thisSuffix;
                }
            }
            $resData['noRgpdDelay'] .= $suffix;

        } catch (Exception $e) {
            $this->terminate($start, $resData, $e);
        }

        $this->terminate($start, $resData);
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->log->log('', $message);
    }

    protected function terminate($start, array $resData, $exception = null)
    {
        $success             = empty($exception);
        $end                 = microtime(true);
        $resData['duration'] = $end - $start;
        $endLogLine          = 'Task ended. Duration : ' . $resData['duration'] . ' s';
        if ($success) {
            $this->log->success($endLogLine);
        } else {
            $this->log->error($endLogLine);
        }

        if (!empty($exception)) {
            /** @var Exception $exception */
            $resData['error']    = $exception;
            $resData['errorMsg'] = $exception->getMessage();
        }

        $result = new ClearContactResult($success ? 200 : 500, $resData);
        do_action('wwp-cron.result.new', $result, static::CommandName);
    }
}
