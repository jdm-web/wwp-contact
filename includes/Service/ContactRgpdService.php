<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Repository\ContactRepository;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;

class ContactRgpdService extends AbstractService
{

    /**
     * @param array $sections
     * @param       $mail
     *
     * @return array
     * @throws ServiceNotFoundException
     */
    public function listConsents(array $sections, $mail)
    {
        /// Init
        $consents = [];
        $messages = [];

        // Check
        if (!is_null($mail)) {
            /** @var ContactRepository $repository */
            $repository = $this->manager->getService('messageRepository');
            $messages   = apply_filters('contact.rgpd.listconsents.messages', $repository->findMessagesFor($mail));

            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $consents[] = [
                        'id'      => $message->getId(),
                        'title'   => trad('contact.message.from', WWP_CONTACT_TEXTDOMAIN) . ' ' . $message->getCreatedAt()->format('d/m/Y H:i:s'),
                        'content' => $this->getMessageConsentContent($message),
                    ];
                }
            }
        }

        $section = [
            'title'               => trad('contact.consents.title', WWP_CONTACT_TEXTDOMAIN),
            'subtitle'            => !empty($consents) ? sprintf(trad('contact.consents.subtitle', WWP_CONTACT_TEXTDOMAIN), count($messages)) : '',
            'beforeDeleteWarning' => trad('contact.consents.beforeDeleteWarning', WWP_CONTACT_TEXTDOMAIN),
            'consents'            => $consents,
        ];

        $sections['contact'] = $section;

        return $sections;
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
                    $consents[] = [
                        'id'      => $message->getId(),
                        'title'   => trad('contact.message.from', WWP_CONTACT_TEXTDOMAIN) . ' ' . $message->getCreatedAt()->format('d/m/Y H:i:s'),
                        'content' => $this->getMessageConsentArray($message),
                    ];
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

        $contactIds = (isset($consents['contact'])) ? $consents['contact'] : [];
        if (count($contactIds) > 0) {
            /** @var ContactRepository $repository */
            $repository      = $this->manager->getService('messageRepository');
            $contactEntities = $repository->findMessagesForEmailAndIds($email, array_keys($contactIds));

            if (count($contactEntities) > 0) {
                /** @var ContactUserDeleterService $deleterService */
                $deleterService = $this->manager->getService('userDeleter');
                $deleterService->removeContactEntities($contactEntities);

                $results['contact'] = new Result(200, [
                    'msg'             => wp_sprintf(__('contact.contents_removed.trad', WWP_CONTACT_TEXTDOMAIN)),
                    'contact_removed' => count($contactIds),
                ]);

            }
        }

        return $results;
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
                    $retention = '∞';
                } else {
                    $retention .= 'days';
                }
                $subTitle = "This form sends an email to the recipient (<strong>" . $formItem->getSendTo() . "</strong>), and stores the following data in the database for a given amount of time (" . $retention . ").";

                if ($formItem->getSaveMsg()) {

                    // Add configured fields
                    $data = json_decode($formItem->getData(), true);
                    if (!empty($data)) {
                        foreach ($data as $fieldId => $fieldOptions) {
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
                            $collectedData[$fieldId] = [
                                'name'      => $field->getName(),
                                'reason'    => $reason,
                                'retention' => $retention,
                            ];
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
     * @param ContactEntity $message
     *
     * @return array[]
     */
    public function getMessageConsentArray(ContactEntity $message)
    {
        $data        = apply_filters('contact.rgpd.consent.data', $message->getData());
        $formTradKey = 'rgpd.form-'.$message->getForm()->getId().'.formname';
        $formTrad    = __($formTradKey, WWP_CONTACT_TEXTDOMAIN);
        $formName    = ($formTrad !== $formTradKey) ? $formTrad : $message->getForm()->getName();
        $content     = [
            'form' => [__('form.trad', WWP_CONTACT_TEXTDOMAIN), $formName],
        ];
        if (!empty($data)) {
            foreach ($data as $field => $value) {
                if ('form' !== $field && 'post' !== $field) {
                    $valueHtml = $this->getValueHtml($field, $value);
                    if (!empty($valueHtml)) {
                        $content[$field] = [__($field . '.trad', WWP_CONTACT_TEXTDOMAIN), $valueHtml];
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
