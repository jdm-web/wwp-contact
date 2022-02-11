<?php

namespace WonderWp\Plugin\Contact\Email;

class ContactCustomerEmail extends AbstractContactEmail
{
    const Name = 'ContactCustomerEmail';

    protected $siteName;

    public function provideSubject(): string
    {
        //Subject : tries to find a specific subject form this form, use the default subject instead
        $defaultSubjectKey = 'default_receipt_subject.form-' . $this->contactEntity->getForm()->getId();
        $subject           = __($defaultSubjectKey, $this->textDomain);
        if ($defaultSubjectKey === $subject) {
            $subject = trad('default_receipt_subject', $this->textDomain);
        }
        return apply_filters('contact.receiptmail.subject', '[' . html_entity_decode($this->siteName, ENT_QUOTES) . '] ' . $subject, $this->contactEntity);

    }

    public function provideBody(): string
    {
        $data = apply_filters('contactMailService.getReceiptBody.data', $this->contactEntity->getData());

        $formid = $this->contactEntity->getForm()->getId();

        //Let's see if there's a specific user mail title set for this form, uses the default user mail title instead
        $titleKey = 'new.receipt.msg.title.form-' . $formid;
        $title    = __($titleKey, $this->textDomain);
        if ($titleKey === $title) {
            $title = trad('new.receipt.msg.title', $this->textDomain);
        }

        //Let's see if there's a specific user mail content set for this form, uses the default user mail content instead
        $contentKey = 'new.receipt.msg.content.form-' . $formid;
        $content    = __($contentKey, $this->textDomain);
        if ($contentKey === $content) {
            $content = trad('new.receipt.msg.content', $this->textDomain);
        }

        $body = '
            <h2>' . $title . '</h2>
            <p>' . $content . ' </p>';

        return apply_filters('wwp-contact.contact_receipt_mail_body', $body, $data, $this->contactEntity);
    }

    public function provideTo(): array
    {
        $contactEntity = $this->contactEntity;

        $contactMail = $contactEntity->getData('mail');
        if (empty($contactMail)) {
            $contactMail = $contactEntity->getData('email');
        }

        //Set Mail To
        //Did the user provide his last name or first name in the form?
        if (!empty($contactEntity->getData('nom'))) {
            $fromName = $contactEntity->getData('nom');
            if (!empty($contactEntity->getData('prenom'))) {
                $fromName = $contactEntity->getData('prenom') . ' ' . $contactEntity->getData('nom');
            }
        } else {
            $fromName = $contactMail;
        }

        return ['mail' => $contactMail, 'name' => $fromName];
    }


}
