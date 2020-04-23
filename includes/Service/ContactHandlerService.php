<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\Form\Field\FieldInterface;
use WonderWp\Component\Form\Field\FileField;
use WonderWp\Component\Form\Field\HoneyPotField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\Form\FormValidatorInterface;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\MailerInterface;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;

class ContactHandlerService
{

    /**
     * @param array                   $data
     * @param FormInterface           $formInstance
     * @param ContactFormEntity       $formItem
     * @param FormValidatorInterface  $formValidator
     * @param ContactPersisterService $persisterService
     * @param string                  $contactEntityName
     * @param string                  $translationDomain
     *
     * @return Result
     */
    public function handleSubmit(
        array $data,
        FormInterface $formInstance,
        ContactFormEntity $formItem,
        FormValidatorInterface $formValidator,
        ContactPersisterService $persisterService,
        $contactEntityName,
        $translationDomain = 'default'
    ) {
        $sent = new Result(500);

        $fields = $formInstance->getFields();
        $data   = $this->handleFiles($fields, $data, $persisterService);
        $formValidator->setFormInstance($formInstance);

        $errors = apply_filters('wwp-contact.contact_handler.validation_errors', $formValidator->validate($data, $translationDomain), $formItem, $data, $formValidator);

        if (empty($errors)) {

            if (isset($data['nonce'])) {
                unset($data['nonce']);
            }

            /** @var ContactEntity $contact */
            $contact = new $contactEntityName();

            $contact
                ->setLocale(get_locale())
                ->setForm($formItem)
                ->setPost($data['post'])
                ->setData($data)
            ;

            $updatedContact = apply_filters('wwp-contact.contact_handler.contact_created', $contact);

            $sent = apply_filters('wwp-contact.contact_handler_service_success', $sent, $data, $updatedContact, $formItem);
        } else {
            $sent->setData(['errors' => $errors]);
        }

        return $sent;
    }

    /**
     * @param FieldInterface[]        $fields
     * @param array                   $data
     * @param ContactPersisterService $persisterService
     *
     * @return array
     */
    protected function handleFiles(array $fields, array $data, ContactPersisterService $persisterService)
    {

        //Look for files
        if (!empty($fields)) {
            foreach ($fields as $f) {
                if ($f instanceof FileField) {
                    $name = str_replace(' ', '_', $f->getName());

                    $file = !empty($_FILES[$name]) ? $_FILES[$name] : null;

                    if (!empty($file)) {
                        $frags    = explode('.', $file['name']);
                        $ext      = end($frags);
                        $fileName = md5($file['name']) . '.' . $ext;
                    } else {
                        $fileName = null;
                    }

                    $res = $persisterService->persistMedia($file, '/contact', $fileName);

                    if ($res->getCode() === 200) {
                        $moveFile            = $res->getData('moveFile');
                        $data[$f->getName()] = $moveFile['url'];
                    }
                }
            }
        }

        return $data;
    }

    public function setupMailDelivery(Result $result, array $data, ContactEntity $contactEntity, ContactFormEntity $formItem, ContactMailService $mailService, MailerInterface $mailer)
    {

        if (isset($data[HoneyPotField::HONEYPOT_FIELD_NAME]) && !empty($data[HoneyPotField::HONEYPOT_FIELD_NAME])) {
            return new Result(200); //On fait croire que ca a marche
        }

        //Send first a notification to the site admin
        $result = $mailService->sendContactMail($contactEntity, $data, $mailer);
        //If this worked and has been sucessfully sent, then send a confirmation to the user that sent the contact message
        if ($result->getCode() === 200) {
            $mailer->reset();
            $receiptResult = $mailService->sendReceiptMail($contactEntity, $data, $mailer);
            $result->addData(['receiptResult' => $receiptResult]);
        }

        return $result;
    }

    public function saveContact(Result $result, array $data, ContactEntity $contactEntity, ContactFormEntity $formItem, ContactPersisterService $persisterService)
    {
        if (isset($data[HoneyPotField::HONEYPOT_FIELD_NAME]) && !empty($data[HoneyPotField::HONEYPOT_FIELD_NAME])) {
            return $result;
        }

        if ($contactEntity->getForm()->getSaveMsg()) {
            $persistRes = $persisterService->persistContactEntity($contactEntity);
            $result->addData(['persist' => $persistRes]);
        } else {
            $result->addData(['persist' => new Result(200, ['msg' => 'No need to persist'])]);
        }

        return $result;
    }
}
