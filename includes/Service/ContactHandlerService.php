<?php
/**
 * Created by PhpStorm.
 * User: jeremydesvaux
 * Date: 15/09/2016
 * Time: 17:09
 */

namespace WonderWp\Plugin\Contact\Service;

use Doctrine\ORM\EntityManager;
use WonderWp\Framework\API\Result;
use WonderWp\Framework\DependencyInjection\Container;
use WonderWp\Framework\Form\Field\FileField;
use WonderWp\Framework\Form\Form;
use WonderWp\Framework\Form\FormValidator;
use WonderWp\Framework\Media\Medias;
use WonderWp\Framework\Service\AbstractService;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;

class ContactHandlerService extends AbstractService
{

    public function handleSubmit(array $data, Form $formInstance, ContactFormEntity $formItem)
    {
        $sent = new Result(500);

        $container = Container::getInstance();
        /** @var EntityManager $em */
        $em = $container->offsetGet('entityManager');
        /** @var FormValidator $formValidator */
        $formValidator = $container->offsetGet('wwp.forms.formValidator');

        $data['datetime'] = new \DateTime();
        $data['locale']   = get_locale();
        $data['form']     = $formItem;

        //Look for files
        $fields = $formInstance->getFields();
        if (!empty($fields)) {
            foreach ($fields as $f) {
                if ($f instanceof FileField) {
                    $name = $f->getName();

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
                        $moveFile    = $res->getData('moveFile');
                        $data[$name] = $moveFile['url'];
                    }
                }
            }
        }

        $errors = $formValidator->setFormInstance($formInstance)->validate($data);
        if (empty($errors)) {
            $contact = new ContactEntity();
            $contact->populate($data);

            //Save Contact - Non adapte pour le moment, ne sauvegarde pas les champs comme il faudrait
            //\WonderWp\trace($contact);
            //$em->persist($contact);
            //$em->flush();
            //\WonderWp\trace($contact);

            $sent = apply_filters('wwp-contact.contact_handler_service_success', $sent, $data, $contact);
        } else {
            $sent->setData(['errors' => $errors]);
        }

        return $sent;
    }
}
