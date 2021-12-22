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
use WonderWp\Plugin\Contact\Result\HandleSubmitResult;
use WonderWp\Plugin\Security\Service\SecurityIpService;

class ContactHandlerService
{

    /**
     * @param array $data
     * @param FormInterface $formInstance
     * @param ContactFormEntity $formItem
     * @param FormValidatorInterface $formValidator
     * @param ContactPersisterService $persisterService
     * @param string $contactEntityName
     * @param string $translationDomain
     *
     * @return Result
     */
    public function handleSubmit(
        array                   $data,
        FormInterface           $formInstance,
        ContactFormEntity       $formItem,
        FormValidatorInterface  $formValidator,
        ContactPersisterService $persisterService,
                                $contactEntityName,
                                $translationDomain = 'default'
    )
    {
        $sent = new HandleSubmitResult(500);

        $fields = $formInstance->getFields();
        $data   = $this->handleFiles($fields, $data, $persisterService);
        $formValidator->setFormInstance($formInstance);

        $errors = apply_filters('wwp-contact.contact_handler.validation_errors', $formValidator->validate($data, $translationDomain), $formItem, $data, $formValidator);

        if (empty($errors)) {

            if (isset($data['nonce'])) {
                unset($data['nonce']);
            }

            /** @var ContactEntity $contact */
            $contact = apply_filters('wwp-contact.contact_handler.contact_entity_creation', new $contactEntityName(), $data);

            $contact
                ->setLocale(get_locale())
                ->setForm($formItem)
                ->setPost($data['post'])
                ->setData($data)
                ->setIp(SecurityIpService::getUserIpAddr());

            $updatedContact = apply_filters('wwp-contact.contact_handler.contact_created', $contact, $data);

            $sent = apply_filters('wwp-contact.contact_handler_service_success', $sent, $data, $updatedContact, $formItem);
        } else {
            $sent->setData(['errors' => $errors]);
        }

        return $sent;
    }

    /**
     * @param FieldInterface[] $fields
     * @param array $data
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

                    //Bot detection
                    if (!empty($data[$f->getName()])) {
                        //We have a posted value for this field : this shouldn't happen when using a file field properly which stores its data in $_FILES instead
                        //Do not process the file and pass the info in the data that this is a bot
                        $data[HoneyPotField::HONEYPOT_FIELD_NAME] = $data[$f->getName()]; //We don't want to make it too obvious
                    }

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

    /**
     * @param Result $result
     * @param array $data
     * @param ContactEntity $contactEntity
     * @param ContactFormEntity $formItem
     * @param ContactMailService $mailService
     * @param MailerInterface $mailer
     *
     * @return Result
     */
    public function setupMailDelivery(Result $result, array $data, ContactEntity $contactEntity, ContactFormEntity $formItem, ContactMailService $mailService, MailerInterface $mailer)
    {
        if ($this->isBot($data, $contactEntity)) {
            return new Result(200, ['type' => '_SuccessfulSubmitResult']); //On fait croire que ca a marche
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
        if ($this->isBot($data, $contactEntity)) {
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

    protected function isBot(array $data, ContactEntity $contactEntity)
    {
        //Check honeypot
        $honeypotValueSet = !empty($data[HoneyPotField::HONEYPOT_FIELD_NAME]) ? $data[HoneyPotField::HONEYPOT_FIELD_NAME] : null;

        //Check contact mail
        $emailValue = $contactEntity->getData('mail');
        if (empty($emailValue)) {
            $emailValue = $contactEntity->getData('email');
        }

        //Check IP Ban
        $ipValue = $contactEntity->getIp();

        //Check Content Value
        $contentValue = $contactEntity->getData('message', '');

        //Check phone value
        $phoneValue = $contactEntity->getData('telephone', '');

        return apply_filters('wwp-security.isBot', false, 'wwp-contact.isBotCheck', $honeypotValueSet, $emailValue, $ipValue, $contentValue, $phoneValue);
    }
}
