<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Repository\ContactRepository;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\RGPD\Entity\RgpdConsentEntity;
use WonderWp\Plugin\RGPD\Entity\RgpdConsentSection;
use WonderWp\Plugin\RGPD\Result\Consent\Delete\RgpdDeletedConsentsResult;

class ContactRgpdService extends AbstractService
{

    /**
     * @param array $sections
     * @param       $mail
     * @param $isIntegrationTesting
     * @return array
     * @throws ServiceNotFoundException
     */
    public function listConsents(array $sections, $mail, $isIntegrationTesting = false)
    {
        /// Init
        $consents = [];

        // Check
        if (!is_null($mail)) {
            /** @var ContactRepository $repository */
            $repository = $this->manager->getService('messageRepository');
            $messages   = apply_filters('contact.rgpd.listconsents.messages', $repository->findMessagesFor($mail));

            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $consent = new RgpdConsentEntity();
                    $consent->setId($message->getId())
                        ->setCategory('contact')
                        ->setTitle('contact.message.from')
                        ->setContent($this->getMessageConsentContent($message))
                        ->setTextdomain(WWP_CONTACT_TEXTDOMAIN);

                    $consents[] = $consent;
                }
            }

            if ($isIntegrationTesting) {
                $consents = $this->addFakeConsents($consents);
            }
        }

        $section = new RgpdConsentSection();
        $section->setTitle('contact.consents.title')
            ->setSubtitle('contact.consents.subtitle')
            ->setBeforeDeleteWarning('contact.consents.beforeDeleteWarning')
            ->setConsents($consents)
            ->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        $sections['contact'] = $section;

        return $sections;
    }

    protected function addFakeConsents($consents)
    {
        $consents[] = $this->generateFakeConsent(1);
        $consents[] = $this->generateFakeConsent(2);

        return $consents;
    }

    protected function generateFakeConsent($consentId)
    {
        $fakeConsent = new RgpdConsentEntity();
        $fakeConsent->setId('test_' . $consentId)
            ->setCategory('contact')
            ->setTitle('contact.message.from')
            ->setContent('<ul class="contact-consent"><li class="rgpd-field-wrap rgpd-field-wrap-form"><span class="field-name">form.trad:</span> <span class="field-value">test</span><li class="rgpd-field-wrap rgpd-field-wrap-nom"><span class="field-name">nom.trad:</span> <span class="field-value">Temporibus reprehend</span><li class="rgpd-field-wrap rgpd-field-wrap-prenom"><span class="field-name">prenom.trad:</span> <span class="field-value">Ex quibusdam ad eum </span><li class="rgpd-field-wrap rgpd-field-wrap-mail"><span class="field-name">mail.trad:</span> <span class="field-value">jeremy.desvaux@wonderful.fr</span><li class="rgpd-field-wrap rgpd-field-wrap-message"><span class="field-name">message.trad:</span> <span class="field-value">Quisquam consequatur</span><li class="rgpd-field-wrap rgpd-field-wrap-rgpd-consent"><span class="field-name">rgpd-consent.trad:</span> <span class="field-value">1</span><li class="rgpd-field-wrap rgpd-field-wrap-srcpage"><span class="field-name">srcpage.trad:</span> <span class="field-value">https://preprod.www.bhns-montpellier.com.wdf-02.ovea.com/</span></ul>')
            ->setTextdomain(WWP_CONTACT_TEXTDOMAIN);

        return $fakeConsent;
    }

    /**
     * @param array $results
     * @param       $mail
     *
     * @return array
     * @throws ServiceNotFoundException
     */
    public function exportConsents(array $results, $mail)
    {
        /// Init
        $consents = [];

        // Check
        if (!is_null($mail)) {
            /** @var ContactRepository $repository */
            $repository = $this->manager->getService('messageRepository');
            $messages   = apply_filters('contact.rgpd.exportconsents.messages', $repository->findMessagesFor($mail));

            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $consent = new RgpdConsentEntity();
                    $consent->setId($message->getId())
                        ->setCategory('contact')
                        ->setTitle('contact.message.from')
                        ->setContent($this->getMessageConsentContent($message))
                        ->setTextdomain(WWP_CONTACT_TEXTDOMAIN);

                    $consents[] = $consent;
                }
            }
        }

        // Results
        $results['contact'] = [
            'consents' => $consents,
        ];

        return $results;
    }

    /**
     * @param $results
     * @param $consents
     * @param $email
     *
     * @return mixed
     * @throws ServiceNotFoundException
     */
    public function deleteConsents($results, $consents, $email)
    {

        $contactIds = $this->extractContactIds($consents);
        $deletedIds = [];
        if (!empty($contactIds['realIds'])) {
            /** @var ContactRepository $repository */
            $repository      = $this->manager->getService('messageRepository');
            $contactEntities = $repository->findMessagesForEmailAndIds($email, $contactIds['realIds']);

            if (count($contactEntities) > 0) {
                /** @var ContactUserDeleterService $deleterService */
                $deleterService = $this->manager->getService('userDeleter');
                $deleterService->removeContactEntities($contactEntities);
                $deletedIds = array_merge($deletedIds, $contactIds['realIds']);
            }
        }
        if (!empty($contactIds['testIds'])) {
            $deletedIds = array_merge($deletedIds, $contactIds['testIds']);
        }
        if (!empty($deletedIds)) {
            $results['contact'] = new RgpdDeletedConsentsResult(
                200,
                $deletedIds,
                wp_sprintf(__('contact.contents_removed.trad', WWP_CONTACT_TEXTDOMAIN))
            );
        }

        return $results;
    }

    protected function extractContactIds($consents)
    {
        $contactIds = (isset($consents['contact'])) ? $consents['contact'] : [];
        $realIds    = [];
        $testIds    = [];

        if (!empty($contactIds)) {
            foreach ($contactIds as $key => $id) {
                if (str_contains($key, 'test')) {
                    $testIds[] = $key;
                } else {
                    $realIds[] = $key;
                }
            }
        }

        return ['realIds' => $realIds, 'testIds' => $testIds];
    }

    /**
     * @param $sections
     *
     * @return mixed
     * @throws ServiceNotFoundException
     */
    public function dataInventory($sections)
    {
        $inventorySection = [
            'title'       => 'Data collected by the Contact plugin',
            'subSections' => [],
        ];

        /** @var ContactFormRepository $formRepo */
        $formRepo = $this->manager->getService('contactFormRepository');
        /** @var ContactFormFieldRepository $fieldRepo */
        $fieldRepo = $this->manager->getService('formFieldRepository');
        /** @var ContactFormEntity[] $forms */
        $forms = $formRepo->findAll();

        if (!empty($forms)) {
            foreach ($forms as $formItem) {

                $collectedData = [];
                $retention     = $formItem->getNumberOfDaysBeforeRemove();
                if ((int)$retention == 0) {
                    $retention        = '<span class="warning">∞</span>';
                    $retentionWarning = '<span class="warning-help" title="La sauvegarde infinie des données n\'est pas recommandée par la règlementation RGPD. Il est préférable de spécifier une rétention en nombre de jours.">?</span>';
                } else {
                    $retention        .= 'days';
                    $retentionWarning = '';
                }
                $subTitle = "This form sends an email to the recipient (<strong>" . $formItem->getSendTo() . "</strong>), and stores the following data in the database for a given amount of time (" . $retention . "). " . $retentionWarning;

                if ($formItem->getSaveMsg()) {

                    // Add configured fields
                    $configuredFields = json_decode($formItem->getData(), true);
                    if (!empty($configuredFields)) {

                        //traitement par groupe, si on a des infos de groupes dans le champ data et si on a plus d'un groupe de champs
                        if (isset($configuredFields["fields"]) && isset($configuredFields["groups"]) && count($configuredFields["groups"]) > 1) {
                            //recupère tous les champs de chaque groupe
                            foreach ($configuredFields["fields"] as $fieldId => $fieldOptions) {
                                $collectedData[$fieldId] = $this->addFieldToDataInventory($fieldId, $fieldOptions, $fieldRepo, $formItem);
                            }
                        } else {
                            //si on a un seul groupe, on recupere les champs => pas de gestion de la notion de groupe
                            if (isset($configuredFields["fields"])) {
                                $configuredFields = $configuredFields["fields"];
                            }

                            foreach ($configuredFields as $fieldId => $fieldOptions) {
                                //Add to inventory
                                $collectedData[$fieldId] = $this->addFieldToDataInventory($fieldId, $fieldOptions, $fieldRepo, $formItem);
                            }
                        }
                    }
                } else {
                    $subTitle = "This form sends an email to the recipient (" . $formItem->getSendTo() . "), but doesn't save any data in the database.";
                }
                $subSection = [
                    'title'          => 'Formulaire ' . $formItem->getId() . ' : ' . $formItem->getName(),
                    'subtitle'       => $subTitle,
                    'collectedDatas' => $collectedData,
                ];

                $inventorySection['subSections'][] = $subSection;
            }
        }

        $sections['contact'] = $inventorySection;

        return $sections;
    }

    /**
     * @param string $fieldId
     * @param array $fieldOptions
     * @param ContactFormFieldRepository $fieldRepo
     * @param ContactFormEntity $formItem
     *
     * @return array
     */
    protected function addFieldToDataInventory($fieldId, array $fieldOptions, ContactFormFieldRepository $fieldRepo, ContactFormEntity $formItem)
    {
        /** @var ContactFormFieldEntity $field */
        $field     = $fieldRepo->find($fieldId);
        $reasonKey = $field->getName() . 'help.trad';
        $reason    = __($reasonKey);
        if ($reason === $reasonKey) {
            $reason = '';
        }
        $retention = $formItem->getNumberOfDaysBeforeRemove();
        if ((int)$retention == 0) {
            $retention = '∞';
        } else {
            $retention .= 'days';
        }

        return [
            'name'      => ContactFormService::getTranslation($formItem->getId(), $field->getName()),
            'reason'    => $reason,
            'retention' => $retention,
        ];
    }

    /**
     * @param ContactEntity $message
     *
     * @return array[]
     */
    public function getMessageConsentArray(ContactEntity $message)
    {
        $data            = apply_filters('contact.rgpd.consent.data', $message->getData());
        $formTradKey     = 'rgpd.form-' . $message->getForm()->getId() . '.formname';
        $formTrad        = __($formTradKey, WWP_CONTACT_TEXTDOMAIN);
        $formName        = ($formTrad !== $formTradKey) ? $formTrad : $message->getForm()->getName();
        $content         = [
            'form' => [__('form.trad', WWP_CONTACT_TEXTDOMAIN), $formName],
        ];
        $fieldsToExclude = apply_filters('wwp-contact.rgpd.msgContentArray.fieldsToExclude', ['form', 'post'], $message);
        if (!empty($data)) {
            foreach ($data as $field => $value) {
                if (!in_array($field, $fieldsToExclude)) {
                    $valueHtml = $this->getValueHtml($field, $value);
                    if (!empty($valueHtml)) {
                        $content[$field] = [ContactFormService::getTranslation($message->getForm()->getId(), $field), $valueHtml];
                    }
                }
            }
        }

        return $content;
    }

    /**
     * @param ContactEntity $message
     *
     * @return string
     */
    public function getMessageConsentContent(ContactEntity $message)
    {
        $contentArray = $this->getMessageConsentArray($message);

        $content = '<ul class="contact-consent">';

        if (!empty($contentArray)) {
            foreach ($contentArray as $i => $f) {
                $content .= '<li class="rgpd-field-wrap rgpd-field-wrap-' . $i . '"><span class="field-name">' . $f[0] . ':</span> <span class="field-value">' . $f[1] . '</span>';
            }
        }

        $content .= '</ul>';

        return $content;
    }

    /**
     * @param $field
     * @param $value
     *
     * @return string
     */
    public function getValueHtml($field, $value)
    {
        $valueHtml = '';
        if (!empty($value) && !is_null($value)) {
            $container = Container::getInstance();
            $em        = $container->offsetGet('entityManager');
            /** @var ContactFormFieldEntity $field */
            $field = $em->getRepository(ContactFormFieldEntity::class)->findOneByName($field);
            if ($field) {
                if (preg_match('/FileField/', $field->getType())) {
                    $valueHtml .= '<a target="_blank" href="' . $value . '">' . __('contact_file_download', WWP_CONTACT_TEXTDOMAIN) . '</a>';
                } elseif (is_array($value) || is_object($value)) {
                    $valueHtml .= json_encode($value);
                } else {
                    $valueHtml .= $value;
                }
            } else {
                $valueHtml .= $value;
            }
        } else {
            $valueHtml .= $value;
        }

        return $valueHtml;
    }
}
