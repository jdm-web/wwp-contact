<?php

namespace WonderWp\Plugin\Contact\Service\Mail;

use WonderWp\Component\Form\Field\SelectField;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\MailerInterface;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Email\AbstractContactEmail;
use WonderWp\Plugin\Contact\Email\ContactAdminEmail;
use WonderWp\Plugin\Contact\Email\ContactCustomerEmail;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Exception\EmailException;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;

class ContactMailService extends AbstractService
{
    /**
     * @param ContactEntity $contact
     * @return AbstractContactEmail[]
     * @throws EmailException
     * @throws ServiceNotFoundException
     */
    public function getEmailsFor(ContactEntity $contact): array
    {
        $emailNames = static::getEmailNames();
        $emails     = [];
        if (!empty($emailNames)) {
            foreach ($emailNames as $emailName) {
                $emails[$emailName] = $this->getEmailFor($emailName, $contact);
            }
        }

        return $emails;
    }

    /**
     * @return array
     */
    public static function getEmailNames(): array
    {
        return [
            ContactAdminEmail::Name,
            ContactCustomerEmail::Name,
        ];
    }

    /**
     * @param $emailName
     * @param ContactEntity $contact
     * @return AbstractContactEmail
     * @throws ServiceNotFoundException
     * @throws EmailException
     */
    public function getEmailFor($emailName, ContactEntity $contact): AbstractContactEmail
    {
        $email = $this->getEmail($emailName);
        $email->setContactEntity($contact)->fill();
        return $email;
    }

    /**
     * @param $emailName
     * @return AbstractContactEmail
     * @throws ServiceNotFoundException
     */
    public function getEmail($emailName): AbstractContactEmail
    {
        return $this->manager->getService($emailName);
    }

    /**
     * @param ContactEntity $contactEntity
     * @param array $data
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

}
