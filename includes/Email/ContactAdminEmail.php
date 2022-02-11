<?php

namespace WonderWp\Plugin\Contact\Email;


use WonderWp\Component\Mailing\MailerInterface;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Exception\EmailException;

class ContactAdminEmail extends AbstractContactEmail
{
    const Name = 'ContactAdminEmail';

    public function provideSubject(): string
    {
        $contactEntity = $this->contactEntity;

        $formid  = $contactEntity->getForm()->getId();
        $subject = '[' . $contactEntity->getForm()->getName() . '] - ';

        //Do we have a default subject part specific to this form?
        $defaultSubjectPartKey = 'default_subject.form-' . $formid;
        $defaultSubjectPart    = __($defaultSubjectPartKey, $this->textDomain);
        //If no use the default subject for admin mails
        if ($defaultSubjectPartKey === $defaultSubjectPart) {
            $defaultSubjectPart = trad('default_subject', $this->textDomain);
        }

        //Before we use the default subject part, let's see if there's something more specific coming from the posted form data
        if (!empty($data) && !empty($data['sujet'])) {
            $chosenSubject = $contactEntity->getData('sujet');
            //Is there a dropdown named sujet with a value?
            if (!empty($data['sujet']['sujets']) && !empty($data['sujet']['sujets'][$chosenSubject]) && !empty($data['sujet']['sujets'][$chosenSubject]['text'])) {
                $subject .= $data['sujet']['sujets'][$chosenSubject]['text'];
            } elseif (is_string($data['sujet'])) { //Is there a field named specifically sujet?
                $subject .= $data['sujet'];
            } else { //Nothing specific, let's use the default subject
                $subject .= $defaultSubjectPart;
            }
        } else {
            $subject .= $defaultSubjectPart;
        }
        $fromData = $this->provideFrom();

        return stripslashes(apply_filters('contact.mail.subject', $subject . ' - ' . $fromData['mail'], $contactEntity));
    }

    public function provideBody(): string
    {
        $contactEntity = $this->contactEntity;
        $data          = apply_filters('contactMailService.getBody.data', $contactEntity->getData());

        $formid = $contactEntity->getForm()->getId();

        //Let's see if there's a specific admin mail title set for this form, uses the default admin mail title instead
        $titleKey = 'new.contact.msg.title.form-' . $formid;
        $title    = __($titleKey, $this->textDomain);
        if ($titleKey === $title) {
            $title = trad('new.contact.msg.title', $this->textDomain);
        }

        //Let's see if there's a specific admin mail content set for this form, uses the default admin mail content instead
        $contentKey = 'new.contact.msg.intro.form-' . $formid;
        $content    = __($contentKey, $this->textDomain);
        if ($contentKey === $content) {
            $content = trad('new.contact.msg.intro', $this->textDomain);
        }

        $mailContent = '
        <h2>' . $title . '</h2>
        <p>' . $content . ': </p>
        <div>';
        //Add contact infos
        $unnecessary = ['id', 'datetime', 'locale', 'sentto', 'form'];

        if (!empty($data)) {
            foreach ($data as $column_name => $val) {
                if (!in_array($column_name, $unnecessary)) {
                    //$val = stripslashes(str_replace('\r\n', '<br />', $contactEntity->{$column_name}));
                    /**if ($column_name == 'sujet') {
                     * $val = $subject;
                     * }*/
                    if ($column_name == 'post' && $val > 0) {
                        $post = get_post($val);
                        $val  = $post->post_title;
                    }
                    $label = trad($column_name . '.trad', $this->textDomain);
                    if (!empty($val)) {
                        if (is_array($val) || is_object($val)) {
                            $val = json_encode($val);
                        }
                        $mailContent .= '<p><strong>' . $label . ':</strong> <span>' . str_replace('\\', '', $val) . '</span></p>';
                    }
                }
            }
        }
        $mailContent .= '
                    </div>';
        if ($contactEntity->getForm()->getSaveMsg()) {
            $mailContent .= '
                    <p>' . trad('contact.msg.registered.bo', $this->textDomain) . '</p>
                    ';
        }

        return apply_filters('wwp-contact.contact_mail_content', $mailContent, $data, $contactEntity);
    }

    /**
     * If contact detail in form, use them
     * Else, use site contact from
     *
     * @param ContactEntity $contactEntity
     *
     * @return array
     */
    public function provideFrom()
    {
        $contactEntity = $this->contactEntity;
        //Did the user provide a mail address in the form?
        $from = $contactEntity->getData('mail');
        if (!empty($from)) {
            $fromMail = $from;
            //Did the user provide his last name or first name in the form?
            if (!empty($contactEntity->getData('nom'))) {
                $fromName = $contactEntity->getData('nom');
                if (!empty($contactEntity->getData('prenom'))) {
                    $fromName = $contactEntity->getData('prenom') . ' ' . $contactEntity->getData('nom');
                }
            } else {
                $fromName = $fromMail;
            }
        } else {
            //Use info saved in the website settings
            $fromMail = $this->fromMail;
            $fromName = $this->fromName;
        }

        return [
            'mail' => $fromMail,
            'name' => $fromName
        ];
    }

    public function fill()
    {
        if (empty($this->contactEntity)) {
            throw new EmailException("email.missing.contactEntity");
        }

        //Add to
        $to = $this->provideTo();
        if (!empty($to['mail'])) {
            //Several email founds
            if (str_contains($to['mail'], ContactManager::multipleAddressSeparator)) {
                $toMails = explode(ContactManager::multipleAddressSeparator, $to['mail']);
                if (!empty($toMails)) {
                    foreach ($toMails as $mailTo) {
                        $this->mailer->addTo($mailTo, $mailTo);
                    }
                }
            } else {
                $this->mailer->addTo($to['mail'], $to['name']);
            }
        }
        $this->mailer->addTo($to['mail'], $to['name']);

        //Set Mail cc
        $formItem = $this->contactEntity->getForm();
        $ccMail   = $formItem->getCc();
        if (!empty($ccMail)) {
            //Several email founds
            if (str_contains($ccMail, ContactManager::multipleAddressSeparator)) {
                $ccMails = explode(ContactManager::multipleAddressSeparator, $ccMail);
                if (!empty($ccMails)) {
                    foreach ($ccMails as $mailTo) {
                        $this->mailer->addCc($mailTo, $mailTo);
                    }
                }
            } else {
                $this->mailer->addCc($ccMail, $ccMail);
            }
        }

        //Add from
        $from = $this->provideFrom();
        $this->mailer->setFrom($from['mail'], $from['name']);

        //Set subject
        $this->mailer->setSubject($this->provideSubject());

        //Set Body
        $this->mailer->setBody($this->provideBody());

        //Set Reply To as well
        $fromData = $this->provideFrom();
        $this->mailer->setReplyTo($fromData['mail'], $fromData['name']);

        return $this;
    }

    public function provideTo(): array
    {
        $contactEntity = $this->getContactEntity();

        $toMail = '';

        //Check for dest in form entity
        $formEntity = $contactEntity->getForm();
        if ($formEntity && $formEntity instanceof ContactFormEntity) {
            $toMail = $formEntity->getSendTo();
        }

        //No dest found in form entity
        if (empty($toMail)) {
            $toMail = $this->toMail;
        }

        $toMail = apply_filters('wwp-contact.form.toMail', $toMail, $contactEntity, $contactEntity->getData());

        $toData = [
            'name' => $toMail,
            'mail' => $toMail
        ];

        return $toData;
    }


}
