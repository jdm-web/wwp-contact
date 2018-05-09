<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;

class ContactMailService extends AbstractService
{
    /** @var MailerInterface */
    protected $mailer;

    /**
     * ContactMailService constructor.
     *
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer) { $this->mailer = $mailer; }

    /**
     * The mail that is sent tp the site admin(s)
     * @param ContactEntity $contactEntity
     * @param array         $data
     *
     * @return Result
     */
    public function sendContactMail(ContactEntity $contactEntity, array $data)
    {
        $formItem  = $contactEntity->getForm();
        //$formData  = json_decode($formItem->getData());

        /** @var MailerInterface $mail */
        $mail = $this->mailer;

        //Set Mail From
        $mail->setFrom(get_option('wonderwp_email_frommail'), get_option('wonderwp_email_fromname'));

        //Set Reply To as well
        list($fromMail, $fromName) = $this->getMailFrom($contactEntity);
        $mail->setReplyTo($fromMail, $fromName);

        //Set Mail To
        $toMail = $this->getMailTo($contactEntity, $data);
        if (!empty($toMail)) {
            //Several email founds
            if (strpos($toMail, ContactManager::multipleAddressSeparator) !== false) {
                $toMails = explode(ContactManager::multipleAddressSeparator, $toMail);
                if (!empty($toMails)) {
                    foreach ($toMails as $mailTo) {
                        $mail->addTo($mailTo, $mailTo);
                    }
                }
            } else {
                $mail->addTo($toMail, $toMail);
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
                        $mail->addCc($mailTo, $mailTo);
                    }
                }
            } else {
                $mail->addCc($ccMail, $ccMail);
            }
        }
        /**
         * Subject
         */
        $chosenSubject = $contactEntity->getData('sujet');
        $subject       = '['.$contactEntity->getForm()->getName().'] - ';

        if (!empty($data) && !empty($data['sujet'])) {
            if (!empty($data['sujet']['sujets']) && !empty($data['sujet']['sujets'][$chosenSubject]) && !empty($data['sujet']['sujets'][$chosenSubject]['text'])) {
                $subject .= $data['sujet']['sujets'][$chosenSubject]['text'];
            } elseif (is_string($data['sujet'])) {
                $subject .= $data['sujet'];
            } else {
                $subject .= __('default_subject', WWP_CONTACT_TEXTDOMAIN);
            }
        } else {
            $subject .= __('default_subject', WWP_CONTACT_TEXTDOMAIN);
        }
        $mail->setSubject(apply_filters('contact.mail.subject', $subject . ' - ' . $fromMail, $contactEntity));

        /**
         * Body
         */
        $body = $this->getBody($contactEntity, $subject, $data);
        $mail->setBody($body);

        //$mail->addBcc('jeremy.desvaux+bcc@wonderful.fr','JD BCC');
        //$mail->addCc('jeremy.desvaux+cc@wonderful.fr','JD CC');

        /**
         * Envoi
         */

        $result = $mail->send();

        return $result;
    }

    /**
     * The mail that is sent to the person that used the contact form
     * @param ContactEntity $contactEntity
     * @param array         $data
     *
     * @return Result
     */
    public function sendReceiptMail(ContactEntity $contactEntity, array $data)
    {
        if (empty($contactEntity->getData('mail'))) {
            return new Result(500, ['msg' => 'No mail to send to']);
        }

        $mail = $this->mailer;

        //Set Mail From
        $fromMail = get_option('wonderwp_email_frommail');
        $fromName = get_option('wonderwp_email_fromname');
        $mail->setFrom($fromMail, $fromName);

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
        $mail->addTo($contactEntity->getData('mail'), $fromName);

        //Subject
        $subject = __('default_receipt_subject', WWP_CONTACT_TEXTDOMAIN);
        $mail->setSubject('[' . html_entity_decode(get_bloginfo('name'), ENT_QUOTES) . '] ' . $subject);

        //Body
        $body = $this->getReceiptBody($contactEntity, $data);
        $mail->setBody($body);

        //Delivery
        $sent = $mail->send();

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
    private function getMailFrom(ContactEntity $contactEntity)
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
     *
     * @return string
     */
    private function getMailTo(ContactEntity $contactEntity, array $data)
    {
        $formEntity = $contactEntity->getForm();
        $toMail     = '';
        $subject    = $contactEntity->getData('sujet');
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
     * @param ContactEntity $contactEntity
     * @param string        $subject
     * @param array         $data
     *
     * @return string
     */
    private function getBody(ContactEntity $contactEntity, $subject, array $data)
    {

        //\WonderWp\trace($contactEntity);
        $mailContent = '
        <h2>' . __('new.contact.msg.title', WWP_CONTACT_TEXTDOMAIN) . '</h2>
        <p>' . __('new.contact.msg.intro', WWP_CONTACT_TEXTDOMAIN) . ': </p>
        <div>';
        //Add contact infos
        $infosChamps = array_keys($contactEntity->getFields());
        $unnecessary = ['id', 'datetime', 'locale', 'sentto', 'form'];

        if (!empty($data)) {
            foreach ($data as $column_name => $val) {
                if (!in_array($column_name, $unnecessary)) {
                    //$val = stripslashes(str_replace('\r\n', '<br />', $contactEntity->{$column_name}));
                    if ($column_name == 'sujet') {
                        $val = $subject;
                    }
                    if($column_name =='post') {
                        $post = get_post($val);
                        $val = $post->post_title;
                    }
                    $label = __($column_name . '.trad', WWP_CONTACT_TEXTDOMAIN);
                    if (!empty($val)) {
                        $mailContent .= '<p><strong>' . $label . ':</strong> <span>' . str_replace('\\','',$val) . '</span></p>';
                    }
                }
            }
        }
        $mailContent .= '
                    </div>';
        if($contactEntity->getForm()->getSaveMsg()) {
            $mailContent .= '
                    <p>' . __('contact.msg.registered.bo', WWP_CONTACT_TEXTDOMAIN) . '</p>
                    ';
        }

        return apply_filters('wwp-contact.contact_mail_content', $mailContent, $data, $contactEntity);
    }

    /**
     * @param ContactEntity $contactEntity
     * @param array         $data
     *
     * @return string
     */
    private function getReceiptBody(ContactEntity $contactEntity, array $data = [])
    {

        $formid = $contactEntity->getForm()->getId();

        $titleKey = 'new.receipt.msg.title.form-'.$formid;
        $title = __($titleKey,WWP_CONTACT_TEXTDOMAIN);
        if($titleKey === $title){
            $title =  __('new.receipt.msg.title', WWP_CONTACT_TEXTDOMAIN);
        }

        $contentKey = 'new.receipt.msg.content.form-'.$formid;
        $content = __($contentKey,WWP_CONTACT_TEXTDOMAIN);
        if($contentKey === $content) {
            $content =  __('new.receipt.msg.content', WWP_CONTACT_TEXTDOMAIN);
        }

        $mailContent = '
            <h2>' . $title . '</h2>
            <p>' . $content . ' </p>';

        return apply_filters('wwp-contact.contact_receipt_mail_content', $mailContent, $data, $contactEntity);
    }
}
