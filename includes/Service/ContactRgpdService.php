<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Framework\API\Result;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Repository\ContactRepository;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;

class ContactRgpdService
{
    /** @var ContactManager */
    protected $manager;

    /**
     * ContactRgpdService constructor.
     *
     * @param ContactManager $manager
     */
    public function __construct(ContactManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return ContactManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param ContactManager $manager
     *
     * @return static
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    public function exportConsents(array $results, $mail)
    {
        /// Init
        $consents = [];

        // Check
        if (!is_null($mail)) {
            /** @var ContactRepository $repository */
            $repository = $this->manager->getService('messageRepository');
            $messages   = $repository->findMessagesFor($mail);

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

        // Results
        $results['contact'] = [
            'consents' => $consents,
        ];
        return $results;
    }

    public function listConsents(array $sections, $mail)
    {
        /// Init
        $consents = [];

        // Check
        if (!is_null($mail)) {
            /** @var ContactRepository $repository */
            $repository = $this->manager->getService('messageRepository');
            $messages   = $repository->findMessagesFor($mail);

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
            'title'    => trad('contact.consents.title', WWP_CONTACT_TEXTDOMAIN),
            'subtitle' => !empty($consents) ? sprintf(trad('contact.consents.subtitle', WWP_CONTACT_TEXTDOMAIN), count($messages)) : '',
            'beforeDeleteWarning' => trad('contact.consents.beforeDeleteWarning', WWP_CONTACT_TEXTDOMAIN),
            'consents' => $consents,
        ];

        $sections['contact'] = $section;

        return $sections;
    }

    public function getMessageConsentContent(ContactEntity $message)
    {
        $data = $message->getData();
        $html = '<ul class="contact-consent">';
        foreach ($data as $field => $value) {
            if ('form' !== $field && 'post' !== $field) {
                $valueHtml = $this->getValueHtml($field, $value);
                if(!empty($valueHtml)) {
                    $html .= '<li><span class="field-name">' . __($field . '.trad', WWP_CONTACT_TEXTDOMAIN) . '</span>: <span class="field-value">' . $valueHtml . '</span>';
                }
            }
        }
        $html .= '</ul>';

        return $html;
    }

    public function getValueHtml($field, $value)
    {
        $valueHtml = '';
        if (!empty($value) && !is_null($value)) {
            $container = Container::getInstance();
            $em        = $container->offsetGet('entityManager');
            $field     = $em->getRepository(ContactFormFieldEntity::class)->findOneByName($field);
            if ($field) {
                if (preg_match('/FileField/', $field->getType())) {
                    $valueHtml .= '<a target="_blank" href="' . $value . '">' . __('contact_file_download', WWP_CONTACT_TEXTDOMAIN) . '</a>';
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
}
