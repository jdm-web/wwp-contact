<?php

namespace WonderWp\Plugin\Contact\Service;

use function WonderWp\Framework\trace;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Repository\ContactRepository;

class ContactRgpdService
{
    /** @var ContactManager */
    protected $manager;

    /**
     * ContactRgpdService constructor.
     *
     * @param ContactManager $manager
     */
    public function __construct(ContactManager $manager) { $this->manager = $manager; }

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
        $messages   = $repository->findMessagesFor($mail);
        $consents   = [];

        if (!empty($messages)) {
            foreach ($messages as $message) {
                $consents[] = [
                    'id'    => $message->getId(),
                    'title' => trad('contact.message.from', WWP_CONTACT_TEXTDOMAIN) . ' ' . $message->getCreatedAt()->format('d/m/Y H:i:s')
                ];
            }
        }

        $section = [
            'title'    => trad('contact.consents', WWP_CONTACT_TEXTDOMAIN),
            'consents' => $consents,
        ];

        $sections['contact'] = $section;

        /*$mk = '
        <div class="consents-wrap contact-consent-wrap">
            <div class="container">
                <h3>' . trad('contact.consents', WWP_CONTACT_TEXTDOMAIN) . '</h3>';

                if (!empty($consents)) {
                    $mk.='<div class="consents">';
                        foreach($consents as $i=>$consent){
                            $mk.='<div class="consent-item">
                                <input name="consents[contact]['.$i.']" type="checkbox" id="consents-contact-'.$i.'" class="checkbox"/>
                                <label for="consents-contact-'.$i.'">'.trad('contact.message.from',WWP_CONTACT_TEXTDOMAIN).' '.$consent->getCreatedAt()->format('d/m/Y H:i:s').'</label>
                            </div>';
                        }
                    $mk.='</div>';
                } else {
                    $mk .= trad('no.contact.consent.found', WWP_CONTACT_TEXTDOMAIN);
                }

        $mk .= '
            </div>            
        </div>';*/

        return $sections;
    }

    public function deleteConsents()
    {

    }
}
