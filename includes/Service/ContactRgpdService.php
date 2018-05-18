<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Framework\API\Result;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Repository\ContactRepository;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
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

        // Filters
        add_filter('rgpd.consents.deletion', [$this, 'deleteConsents'], 10, 2);
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

    public function listConsents(array $sections, $mail)
    {
        /** @var ContactRepository $repository */
        $repository = $this->manager->getService('messageRepository');
        $messages = $repository->findMessagesFor($mail);
        $consents = [];

        if (!empty($messages)) {
            foreach ($messages as $message) {
                $consents[] = [
                    'id' => $message->getId(),
                    'title' => trad('contact.message.from', WWP_CONTACT_TEXTDOMAIN).' '.$message->getCreatedAt()->format('d/m/Y H:i:s'),
                    'content' => $this->getMessageConsentContent($message),
                ];
            }
        }

        $section = [
            'title' => trad('contact.consents', WWP_CONTACT_TEXTDOMAIN),
            'consents' => $consents,
        ];

        $sections['contact'] = $section;

        return $sections;
    }

    public function getMessageConsentContent($message)
    {
        $data = $message->getData();
        $html = "<ul class='contact-consent'>";
        foreach ($data as $field => $value) {
            if ('form' !== $field && 'post' !== $field) {
                $html .= "<li><span class='field-name'>".__($field.'.trad', WWP_CONTACT_TEXTDOMAIN)."</span>: <span class='field-value'>".$this->getValueHtml($field, $value).'</span>';
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
            $em = $container->offsetGet('entityManager');
            $field = $em->getRepository(ContactFormFieldEntity::class)->findOneByName($field);
            if ($field) {
                if (preg_match('/FileField/', $field->getType())) {
                    $valueHtml .= '<a target="_blank" href="'.$value.'">'.__('contact_file_download', WWP_CONTACT_TEXTDOMAIN).'</a>';
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

    public function deleteConsents($consents, $email)
    {
        $contactIds = (isset($consents['contact'])) ? $consents['contact'] : [];
        if (count($contactIds) > 0) {
            /** @var ContactRepository $repository */
            $repository = $this->manager->getService('messageRepository');
            $contactEntities = $repository->findMessagesForEmailAndIds($email, array_keys($contactIds));

            if (count($contactEntities) > 0) {
                $deleterService = $this->manager->getService('userDeleter');
                $deleterService->removeContactEntities($contactEntities);
            }
            return New Result(200, [
              'msg' => wp_sprintf(__('contact.contents_removed.trad', WWP_CONTACT_TEXTDOMAIN)),
            ]);
        }
    }
}
