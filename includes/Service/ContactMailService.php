<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\MailerInterface;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;

class ContactMailService
{

    protected $options;

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     *
     * @return static
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * ContactMailService constructor.
     *
     * @param $options
     */
    public function __construct($options) { $this->options = $options; }

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
        $mailer->setFrom($this->getOption('wonderwp_email_frommail'), $this->getOption('wonderwp_email_fromname'));

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
            return new Result(403, ['msg' => "This mail does not have any recipient"]);
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
        $subject = $this->getAdminMailSubject($contactEntity);
        $mailer->setSubject(stripslashes(apply_filters('contact.mail.subject', $subject . ' - ' . $fromMail, $contactEntity)));

        /**
         * Body
         */
        $body = $this->getBody($contactEntity, $subject, $data);
        $mailer->setBody($body);

        /**
         * Envoi
         */

        $result = $mailer->send();

        return $result;
    }

    /**
     * @param ContactEntity $contactEntity
     *
     * @return string
     */
    protected function getAdminMailSubject(ContactEntity $contactEntity)
    {
        $formid  = $contactEntity->getForm()->getId();
        $subject = '[' . $contactEntity->getForm()->getName() . '] - ';

        //Do we have a default subject part specific to this form?
        $defaultSubjectPartKey = 'default_subject.form-' . $formid;
        $defaultSubjectPart    = __($defaultSubjectPartKey, WWP_CONTACT_TEXTDOMAIN);
        //If no use the default subject for admin mails
        if ($defaultSubjectPartKey === $defaultSubjectPart) {
            $defaultSubjectPart = trad('default_subject', WWP_CONTACT_TEXTDOMAIN);
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

        return $subject;
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
        $fromMail = $this->getOption('wonderwp_email_frommail');
        $fromName = $this->getOption('wonderwp_email_fromname');
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

        //Subject : tries to find a specific subject form this form, use the default subject instead
        $defaultSubjectKey = 'default_receipt_subject.form-' . $contactEntity->getForm()->getId();
        $subject           = __($defaultSubjectKey, WWP_CONTACT_TEXTDOMAIN);
        if ($defaultSubjectKey === $subject) {
            $subject = trad('default_receipt_subject', WWP_CONTACT_TEXTDOMAIN);
        }
        $mailer->setSubject(apply_filters('contact.receiptmail.subject', '[' . html_entity_decode($this->getOption('site_name'), ENT_QUOTES) . '] ' . $subject, $contactEntity));

        //Body
        $body = apply_filters('wwp-contact.contact_receipt_mail_body', $this->getReceiptBody($contactEntity, $data), $data, $contactEntity);
        $mailer->setBody($body);

        //Delivery
        $opts = apply_filters('wwp-contact.receiptmail.options', $this->getReceiptOptions($contactMail, $body), $contactMail);
        $sent = $mailer->send($opts);

        return $sent;

    }

    protected function getReceiptOptions($contactMail, $body)
    {
        return [];
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
            $fromMail = $this->getOption('wonderwp_email_frommail');
            $fromName = $this->getOption('wonderwp_email_fromname');
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
        $toMail = '';

        //Check for dest in form entity
        $formEntity = $contactEntity->getForm();
        if ($formEntity && $formEntity instanceof ContactFormEntity) {
            $toMail = $formEntity->getSendTo();
        }

        //No dest found in form entity
        if (empty($toMail)) {
            $toMail = $this->getOption('wonderwp_email_tomail');
        }

        return apply_filters('wwp-contact.form.toMail', $toMail, $contactEntity, $data);
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

        $formid = $contactEntity->getForm()->getId();

        //Let's see if there's a specific admin mail title set for this form, uses the default admin mail title instead
        $titleKey = 'new.contact.msg.title.form-' . $formid;
        $title    = __($titleKey, WWP_CONTACT_TEXTDOMAIN);
        if ($titleKey === $title) {
            $title = trad('new.contact.msg.title', WWP_CONTACT_TEXTDOMAIN);
        }

        //Let's see if there's a specific admin mail content set for this form, uses the default admin mail content instead
        $contentKey = 'new.contact.msg.intro.form-' . $formid;
        $content    = __($contentKey, WWP_CONTACT_TEXTDOMAIN);
        if ($contentKey === $content) {
            $content = trad('new.contact.msg.intro', WWP_CONTACT_TEXTDOMAIN);
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
                    if ($column_name == 'sujet') {
                        $val = $subject;
                    }
                    if ($column_name == 'post' && $val > 0) {
                        $post = get_post($val);
                        $val  = $post->post_title;
                    }
                    $label = trad($column_name . '.trad', WWP_CONTACT_TEXTDOMAIN);
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

        //Let's see if there's a specific user mail title set for this form, uses the default user mail title instead
        $titleKey = 'new.receipt.msg.title.form-' . $formid;
        $title    = __($titleKey, WWP_CONTACT_TEXTDOMAIN);
        if ($titleKey === $title) {
            $title = trad('new.receipt.msg.title', WWP_CONTACT_TEXTDOMAIN);
        }

        //Let's see if there's a specific user mail content set for this form, uses the default user mail content instead
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

    /**
     * @param ContactEntity              $contactEntity
     * @param array                      $data
     * @param ContactFormFieldRepository $fieldRepo
     *
     * @return string
     */
    public function findDestViaSubjectData(ContactEntity $contactEntity, array $data, ContactFormFieldRepository $fieldRepo)
    {
        $destFound = [];

        //check if we have a valid form
        $formEntity = $contactEntity->getForm();
        if (!$formEntity || !$formEntity instanceof ContactFormEntity) {
            return '';
        }
        //Check if we have valid form data
        $formData = is_object($formEntity) ? $formEntity->getData() : null;
        if (empty($formData)) {
            return '';
        }
        $formData = json_decode($formData, true);
        if (empty($formData) || empty($formData['fields'])) {
            return '';
        }
        //Extract field ids from form data
        $fieldIds = array_keys($formData['fields']);
        if (empty($fieldIds)) {
            return '';
        }
        //Find those fields that are configured in this form and that are also select fields.
        //Because potential dest emails can be configured only in select fields
        /** @var ContactFormFieldEntity[] $subjectFields */
        $subjectFields = $fieldRepo->findBy([
            'id'   => $fieldIds,
            'type' => addslashes(SelectField::class),
        ]);
        if (empty($subjectFields)) {
            return '';
        }

        //For each select field found, which is a potential subject
        foreach ($subjectFields as $subjectField) {
            $postedValue = isset($data[$subjectField->getName()]) ? $data[$subjectField->getName()] : null;
            if (empty($postedValue)) {
                //If no value provided, no need to find a dest for this field
                continue;
            }
            /** @var array $choices */
            $choices = $subjectField->getOption('choices');
            //If no choices provided, no need to find a dest for this field
            if (empty($choices)) {
                return '';
            }
            //Find choice corresponding with postedValue
            foreach ($choices as $choice) {
                if (!empty($choice['value']) && !empty($choice['dest']) && $choice['value'] === $postedValue) {
                    $destFound[] = $choice['dest'];
                }
            }
        }

        return implode(ContactManager::multipleAddressSeparator, $destFound);
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    protected function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }
}
