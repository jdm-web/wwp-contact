<?php

namespace WonderWp\Plugin\Contact\Service\Form\Post\Processor\WP;

use WonderWp\Component\Form\Field\FileField;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostProcessingResult;
use WonderWp\Plugin\Contact\Result\Form\Post\ContactFormPostValidationResult;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;
use WonderWp\Plugin\Contact\Service\Form\Post\Processor\ContactFormPostProcessorInterface;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestProcessor;
use WonderWp\Plugin\Security\Service\SecurityIpService;

class ContactFormPostProcessor extends ContactAbstractRequestProcessor implements ContactFormPostProcessorInterface
{
    public static $ResultClass = ContactFormPostProcessingResult::class;

    /** @var string */
    protected $contactEntityName;

    /** @var ContactPersisterService */
    protected $persisterService;

    /**
     * @param string $contactEntityName
     */
    public function __construct(
        string                  $contactEntityName,
        ContactPersisterService $persisterService
    )
    {
        $this->contactEntityName = $contactEntityName;
        $this->persisterService  = $persisterService;
    }


    public function process(ContactFormPostValidationResult $validationResult): ContactFormPostProcessingResult
    {
        if ($this->isValidationResultInvalid($validationResult)) {
            return $this->processingResultFromValidationResult($validationResult);
        }

        //Bot checking
        if ($validationResult->isBot()) {
            //Return fake sucessful result, that's why we're not calling this->success
            return new ContactFormPostProcessingResult(
                200,
                $validationResult,
                ContactFormPostProcessingResult::FakeSuccess
            );
        }

        $formEntity = $validationResult->getForm();

        //Handle Files upload
        $validatedData = $this->handleFiles($validationResult->getValidatedFiles(), $this->persisterService, $validationResult->getData(), $formEntity->getId());

        //Prepare Contact Entity
        $contactEntity = $this->createContactEntity($this->contactEntityName, $formEntity, $validatedData);
        if ($formEntity->getSaveMsg()) {
            $this->persisterService->persistContactEntity($contactEntity);
        }

        $result = new ContactFormPostProcessingResult(
            200,
            $validationResult,
            ContactFormPostProcessingResult::Error
        );
        $result->setContactEntity($contactEntity);

        return $this->success($result);
    }

    protected function createContactEntity($contactEntityName, ContactFormEntity $formEntity, array $requestData)
    {
        if (isset($requestData['nonce'])) {
            unset($requestData['nonce']);
        }

        /** @var ContactEntity $contact */
        $contact = apply_filters('wwp-contact.contact_handler.contact_entity_creation', new $contactEntityName(), $requestData);

        $postId = $requestData['post'] ?? 0;

        $contactMail = $requestData['mail'] ?? '';
        if (empty($contactMail)) {
            $contactMail = $requestData['email'] ?? '';
        }

        $contact
            ->setLocale(get_locale())
            ->setForm($formEntity)
            ->setPost($postId)
            ->setEmail($contactMail)
            ->setData($requestData)
            ->setIp(SecurityIpService::getUserIpAddr());

        $updatedContact = apply_filters('wwp-contact.contact_handler.contact_created', $contact, $requestData);

        return $updatedContact;
    }

    /**
     * @param array $files
     * @param ContactPersisterService $persisterService
     * @param array $data
     * @return array
     */
    protected function handleFiles(array $files, ContactPersisterService $persisterService, array $data, int $formId)
    {

        //Look for files
        if (!empty($files)) {
            foreach ($files as $fieldName => $file) {
                $frags    = explode('.', $file['name']);
                $ext      = end($frags);
                $fileName = md5($file['name']) . '.' . $ext;

                $res = $persisterService->persistMedia($file, '/wwp-contact/form_' . $formId, $fileName);

                if ($res->getCode() === 200) {
                    $moveFile         = $res->getData('moveFile');
                    $data[$fieldName] = $moveFile['url'];
                }
            }
        }

        return $data;
    }

}
