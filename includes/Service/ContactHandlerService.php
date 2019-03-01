<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\FileField;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\Form\FormValidator;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Media\Medias;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;

class ContactHandlerService extends AbstractService
{

    /** @var FormValidator */
    protected $validator;

    /**
     * ContactHandlerService constructor.
     *
     * @param FormValidator $validator
     */
    public function __construct(FormValidator $validator) { $this->validator = $validator; }

    public function handleSubmit(array $data, FormInterface $formInstance, ContactFormEntity $formItem)
    {
        $sent = new Result(500);

        /** @var FormValidator $formValidator */
        $formValidator = $this->validator;

        //Look for files
        $fields = $formInstance->getFields();
        if (!empty($fields)) {
            foreach ($fields as $f) {
                if ($f instanceof FileField) {
                    $name = str_replace(' ', '_', $f->getName());

                    $file = !empty($_FILES[$name]) ? $_FILES[$name] : null;
                    //if(empty($file) && $formValidator::hasRule($f->getValidationRules(),NotEmpty::class)){

                    //}
                    if (!empty($file)) {
                        $frags    = explode('.', $file['name']);
                        $ext      = end($frags);
                        $fileName = md5($file['name']) . '.' . $ext;
                    } else {
                        $fileName = null;
                    }

                    $res = Medias::uploadTo($file, '/contact', $fileName);

                    if ($res->getCode() === 200) {
                        $moveFile            = $res->getData('moveFile');
                        $data[$f->getName()] = $moveFile['url'];
                    }
                }
            }
        }

        $errors = $formValidator->setFormInstance($formInstance)->validate($data);
        if (empty($errors)) {

            if (isset($data['nonce'])) {
                unset($data['nonce']);
            }

            $contact = new ContactEntity();

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
}
