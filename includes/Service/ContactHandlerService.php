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
use WonderWp\Framework\Mail\Gateways\MandrillMailer;
use WonderWp\Framework\Mail\MailerInterface;
use WonderWp\Framework\Mail\WpMailer;
use WonderWp\Framework\Media\Medias;
use WonderWp\Framework\Service\AbstractService;
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

            //Send Email
            $sent = $this->sendContactMail($contact, $data);
            if ($sent->getCode() === 200) {
                $this->sendReceiptMail($contact, $data);
            }
        } else {
            $sent->setData(['errors' => $errors]);
        }

        return $sent;
    }

    /**
     * @param ContactEntity $contactEntity
     *
     * @return Result
     */
    private function sendContactMail(ContactEntity $contactEntity, array $data)
    {
        $container = Container::getInstance();
        $formItem  = $contactEntity->getForm();
        $formData  = json_decode($formItem->getData());

        /** @var MailerInterface $mail */
        $mail = $container->offsetGet('wwp.emails.mailer');

        //Set Mail From
        $mail->setFrom(get_option('wonderwp_email_frommail'), get_option('wonderwp_email_fromname'));

        //Set Reply To as well
        list($fromMail, $fromName) = $this->getMailFrom($contactEntity);
        $mail->setReplyTo($fromMail, $fromName);

        //Set Mail To
        $toMail = $this->getMailTo($contactEntity, $data);
        if (!empty($toMail)) {
            //Several email founds
            if (strpos($toMail, ',') !== false) {
                $toMails = explode(',', $toMail);
                if (!empty($toMails)) {
                    foreach ($toMails as $mail) {
                        $mail->addTo($mail, $mail);
                    }
                }
            } else {
                $toName = $toMail;
                $mail->addTo($toMail, $toName);
            }
        } else {
            //Erreur pas de dest
        }

        /**
         * Subject
         */
        $chosenSubject = $contactEntity->getSujet();
        $subject       = __('default_subject', WWP_CONTACT_TEXTDOMAIN);
        if (!empty($formData) && !empty($formData->sujet) && !empty($formData->sujet->sujets) && !empty($formData->sujet->sujets->{$chosenSubject}) && !empty($formData->sujet->sujets->{$chosenSubject}->text)) {
            $subject = $formData->sujet->sujets->{$chosenSubject}->text;
        }
        $mail->setSubject(apply_filters('contact.mail.subject', $subject . ' - ' . $fromMail));

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

    private function sendReceiptMail(ContactEntity $contactEntity, array $data)
    {
        if (empty($contactEntity->getMail())) {
            return false;
        }

        $container = Container::getInstance();
        /** @var WpMailer $mail */
        $mail = $container->offsetGet('wwp.emails.mailer');

        //Set Mail From
        $fromMail = get_option('wonderwp_email_frommail');
        $fromName = get_option('wonderwp_email_fromname');
        $mail->setFrom($fromMail, $fromName);

        //Set Mail To
        //Did the user provide his last name or first name in the form?
        if (!empty($contactEntity->getNom())) {
            $fromName = $contactEntity->getNom();
            if (!empty($contactEntity->getPrenom())) {
                $fromName = $contactEntity->getPrenom() . ' ' . $contactEntity->getNom();
            }
        } else {
            $fromName = $fromMail;
        }
        $mail->addTo($contactEntity->getMail(), $fromName);

        //Subject
        $subject = __('default_receipt_subject', WWP_CONTACT_TEXTDOMAIN);
        $mail->setSubject('[' . get_bloginfo('name') . '] ' . $subject);

        $body = $this->getReceiptBody();
        $mail->setBody($body);

        /**
         * Envoi
         */
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
        $from = $contactEntity->getMail();
        if (!empty($from)) {
            $fromMail = $from;
            //Did the user provide his last name or first name in the form?
            if (!empty($contactEntity->getNom())) {
                $fromName = $contactEntity->getNom();
                if (!empty($contactEntity->getPrenom())) {
                    $fromName = $contactEntity->getPrenom() . ' ' . $contactEntity->getNom();
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
     * @return mixed|string|void
     */
    private function getMailTo(ContactEntity $contactEntity, array $data)
    {
        $formEntity = $contactEntity->getForm();
        $toMail     = '';
        $subject    = $contactEntity->getSujet();
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

    private function getBody(ContactEntity $contactEntity, $subject, array $data)
    {
        //\WonderWp\trace($contactEntity);
        $mailContent = '
        <h2>' . __('new.contact.msg.title', WWP_CONTACT_TEXTDOMAIN) . '</h2>
        <p>' . __('new.contact.msg.intro', WWP_CONTACT_TEXTDOMAIN) . ': </p>
        <div>';
        //Add contact infos
        $infosChamps = array_keys($contactEntity->getFields());
        $unnecessary = ['id', 'post', 'datetime', 'locale', 'sentto', 'form'];

        if (!empty($data)) {
            foreach ($data as $column_name => $val) {
                if (!in_array($column_name, $unnecessary)) {
                    //$val = stripslashes(str_replace('\r\n', '<br />', $contactEntity->{$column_name}));
                    if ($column_name == 'sujet') {
                        $val = $subject;
                    }
                    $label = __($column_name . '.trad', WWP_CONTACT_TEXTDOMAIN);
                    if (!empty($val)) {
                        $mailContent .= '<p><strong>' . $label . ':</strong> <span>' . stripslashes($val) . '</span></p>';
                    }
                }
            }
        }
        $mailContent .= '
                    </div>
                    <p>' . __('contact.msg.registered.bo', WWP_CONTACT_TEXTDOMAIN) . '</p>
                    ';

        return apply_filters('wwp-contact.contact_mail_content', $data, $contactEntity);
    }

    private function getReceiptBody()
    {
        $mail = Container::getInstance()->offsetGet('wwp.emails.mailer');

        if (get_class($mail) === MandrillMailer::class) {
            $localeFrags = explode('_', get_locale());
            $mailContent = 'template::accuse-de-reception-message-contact-' . reset($localeFrags);
        } else {

            $mailContent = '
            <h2>' . __('new.receipt.msg.title', WWP_CONTACT_TEXTDOMAIN) . '</h2>
            <p>' . __('new.receipt.msg.content', WWP_CONTACT_TEXTDOMAIN) . ': </p>';

        }

        return apply_filters('wwp-contact.contact_receipt_mail_content');
    }
}
