<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\MailerInterface;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;

class ContactMailService
{

    /**
     * The mail that is sent to the site admin(s)
     *
     * @param ContactEntity   $contactEntity
     * @param array           $data
     * @param MailerInterface $mailer
     *
     * @return Result
     */
    public function sendContactMail(ContactEntity $contactEntity, array $data, MailerInterface $mailer)
    {
        $formItem = $contactEntity->getForm();
        //$formData  = json_decode($formItem->getData());

        //Set Mail From
        $mailer->setFrom(get_option('wonderwp_email_frommail'), get_option('wonderwp_email_fromname'));

        //Set Reply To as well
        list($fromMail, $fromName) = $this->getMailFrom($contactEntity);
        $mailer->setReplyTo($fromMail, $fromName);

        //Set Mail To
        $toMail = $this->getMailTo($contactEntity, $data);
        if (!empty($toMail)) {
            //Several email founds
            if (strpos($toMail, ContactManager::multipleAddressSeparator) !== false) {
                $toMails = explode(ContactManager::multipleAddressSeparator, $toMail);
                if (!empty($toMails)) {
                    foreach ($toMails as $mailTo) {
                        $mailer->addTo($mailTo, $mailTo);
                    }
                }
            } else {
                $mailer->addTo($toMail, $toMail);
            }
        } else {
            //Erreur pas de dest
        }

        //Set Mail cc
        $ccMail = $formItem->getCc();
        if (!empty($ccMail)) {
            //Several email founds
            if (strpos($ccMail, ContactManager::multipleAddressSeparator) !== false) {
                $ccMails = explode(ContactManager::multipleAddressSeparator, $ccMail);
                if (!empty($ccMails)) {
                    foreach ($ccMails as $mailTo) {
                        $mailer->addCc($mailTo, $mailTo);
                    }
                }
            } else {
                $mailer->addCc($ccMail, $ccMail);
            }
        }
        /**
         * Subject
         */
        $chosenSubject = $contactEntity->getData('sujet');
        $subject       = '[' . $contactEntity->getForm()->getName() . '] - ';

        if (!empty($data) && !empty($data['sujet'])) {
            if (!empty($data['sujet']['sujets']) && !empty($data['sujet']['sujets'][$chosenSubject]) && !empty($data['sujet']['sujets'][$chosenSubject]['text'])) {
                $subject .= $data['sujet']['sujets'][$chosenSubject]['text'];
            } elseif (is_string($data['sujet'])) {
                $subject .= $data['sujet'];
            } else {
                $subject .= trad('default_subject', WWP_CONTACT_TEXTDOMAIN);
            }
        } else {
            $subject .= trad('default_subject', WWP_CONTACT_TEXTDOMAIN);
        }
        $mailer->setSubject(apply_filters('contact.mail.subject', $subject . ' - ' . $fromMail, $contactEntity));

        /**
         * Body
         */
        $body = $this->getBody($contactEntity, $subject, $data);
        $mailer->setBody($body);

        //$mailer->addBcc('jeremy.desvaux+bcc@wonderful.fr','JD BCC');
        //$mailer->addCc('jeremy.desvaux+cc@wonderful.fr','JD CC');

        /**
         * Envoi
         */

        $result = $mailer->send();

        return $result;
    }

    /**
     * The mail that is sent to the person that used the contact form
     *
     * @param ContactEntity   $contactEntity
     * @param array           $data
     * @param MailerInterface $mailer
     *
     * @return Result
     */
    public function sendReceiptMail(ContactEntity $contactEntity, array $data, MailerInterface $mailer)
    {
        $contactMail = $contactEntity->getData('mail');
        if (empty($contactMail)) {
            $contactMail = $contactEntity->getData('email');
        }
        if (empty($contactMail)) {
            return new Result(500, ['msg' => 'No mail to send to']);
        }

        //Set Mail From
        $fromMail = get_option('wonderwp_email_frommail');
        $fromName = get_option('wonderwp_email_fromname');
        $mailer->setFrom($fromMail, $fromName);

        //Set Mail To
        //Did the user provide his last name or first name in the form?
        if (!empty($contactEntity->getData('nom'))) {
            $fromName = $contactEntity->getData('nom');
            if (!empty($contactEntity->getData('prenom'))) {
                $fromName = $contactEntity->getData('prenom') . ' ' . $contactEntity->getData('nom');
            }
        } else {
            $fromName = $fromMail;
        }
        $mailer->addTo($contactMail, $fromName);

        //Subject
        $subject = trad('default_receipt_subject', WWP_CONTACT_TEXTDOMAIN);
        $mailer->setSubject('[' . html_entity_decode(get_bloginfo('name'), ENT_QUOTES) . '] ' . $subject);

        //Body
        $body = $this->getReceiptBody($contactEntity, $data);
        $mailer->setBody($body);

        //Delivery
        $sent = $mailer->send();

        return $sent;

    }

    /**
     * If contact detail in form, use them
     * Else, use site contact from
     *
     * @param ContactEntity $contactEntity
     *
     * @return array
     */
    protected function getMailFrom(ContactEntity $contactEntity)
    {
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
            $fromMail = get_option('wonderwp_email_frommail');
            $fromName = get_option('wonderwp_email_fromname');
        }

        return [$fromMail, $fromName];
    }

    /**
     * if subject and subject dest -> send to subject dest
     * else if form dest -> send to form dest
     * else -> send to site dest
     *
     * @param ContactEntity $contactEntity
     * @param array         $data
     *
     * @return string
     */
    protected function getMailTo(ContactEntity $contactEntity, array $data)
    {
        $formEntity = $contactEntity->getForm();
        $toMail  = '';
        $subject = $contactEntity->getData('sujet');
        if (!empty($subject)) {
            $formData = is_object($formEntity) ? $formEntity->getData() : null;
            if (!empty($formData)) {
                $formData = json_decode($formData);
            }
            if (!empty($formData) && !empty($formData->sujet) && !empty($formData->sujet->sujets)) {
                $sujets        = $formData->sujet->sujets;
                $chosenSubject = !empty($sujets->{$subject}) ? $sujets->{$subject} : null;
                if (!empty($chosenSubject) && !empty($chosenSubject->dest)) {
                    $toMail = $chosenSubject->dest;
                }
            }
        }
        //No dest found in subject
        if (empty($toMail)) {
            $toMail = $formEntity->getSendTo();
        }
        //No dest found in form entity
        if (empty($toMail)) {
            $toMail = get_option('wonderwp_email_tomail');
        }

        return $toMail;
    }

    /**
     * This is the email that is sent to the site admin to notify him of a contact request
     *
     * @param ContactEntity $contactEntity
     * @param string        $subject
     * @param array         $data
     *
     * @return string
     */
    protected function getBody(ContactEntity $contactEntity, $subject, array $data)
    {
        $data = apply_filters('contactMailService.getBody.data', $data);

        $mailContent = '
        <h2>' . trad('new.contact.msg.title', WWP_CONTACT_TEXTDOMAIN) . '</h2>
        <p>' . trad('new.contact.msg.intro', WWP_CONTACT_TEXTDOMAIN) . ': </p>
        <div>';
        //Add contact infos
        $unnecessary = ['id', 'datetime', 'locale', 'sentto', 'form'];

        if (!empty($data)) {
            foreach ($data as $column_name => $val) {
                if (!in_array($column_name, $unnecessary)) {
                    //$val = stripslashes(str_replace('\r\n', '<br />', $contactEntity->{$column_name}));
                    if ($column_name == 'sujet') {
                        $val = $subject;
                    }
                    if ($column_name == 'post') {
                        $post = get_post($val);
                        $val  = $post->post_title;
                    }
                    $label = trad($column_name . '.trad', WWP_CONTACT_TEXTDOMAIN);
                    if (!empty($val)) {
                        $mailContent .= '<p><strong>' . $label . ':</strong> <span>' . str_replace('\\', '', $val) . '</span></p>';
                    }
                }
            }
        }
        $mailContent .= '
                    </div>';
        if ($contactEntity->getForm()->getSaveMsg()) {
            $mailContent .= '
                    <p>' . trad('contact.msg.registered.bo', WWP_CONTACT_TEXTDOMAIN) . '</p>
                    ';
        }

        return apply_filters('wwp-contact.contact_mail_content', $mailContent, $data, $contactEntity);
    }

    /**
     * This is the receipt email that is sent to the user that filled the contact form
     *
     * @param ContactEntity $contactEntity
     * @param array         $data
     *
     * @return string
     */
    protected function getReceiptBody(ContactEntity $contactEntity, array $data = [])
    {
        $data = apply_filters('contactMailService.getReceiptBody.data', $data);

        $formid = $contactEntity->getForm()->getId();

        $titleKey = 'new.receipt.msg.title.form-' . $formid;
        $title    = __($titleKey, WWP_CONTACT_TEXTDOMAIN);
        if ($titleKey === $title) {
            $title = trad('new.receipt.msg.title', WWP_CONTACT_TEXTDOMAIN);
        }

        $contentKey = 'new.receipt.msg.content.form-' . $formid;
        $content    = __($contentKey, WWP_CONTACT_TEXTDOMAIN);
        if ($contentKey === $content) {
            $content = trad('new.receipt.msg.content', WWP_CONTACT_TEXTDOMAIN);
        }

        $mailContent = '
            <h2>' . $title . '</h2>
            <p>' . $content . ' </p>';

        return apply_filters('wwp-contact.contact_receipt_mail_content', $mailContent, $data, $contactEntity);
    }
}
