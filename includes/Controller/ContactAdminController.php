<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://digital.wonderful.fr
 * @since      1.0.0
 *
 * @package    Wonderwp
 * @subpackage Wonderwp/admin
 */

namespace WonderWp\Plugin\Contact\Controller;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\FormViewReadOnly;
use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\PluginSkeleton\ListTable\AbstractListTable;
use WonderWp\Component\Service\ServiceInterface;
use WonderWp\Component\Template\Views\VueFrag;
use WonderWp\Plugin\Contact\ContactManager;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Form\ContactForm;
use WonderWp\Plugin\Contact\Form\ContactFormFieldForm;
use WonderWp\Plugin\Contact\Form\ContactFormForm;
use WonderWp\Plugin\Contact\ListTable\ContactFormFieldListTable;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Repository\ContactFormRepository;
use WonderWp\Plugin\Contact\Repository\ContactRepository;
use WonderWp\Plugin\Contact\Service\ContactMailService;
use WonderWp\Plugin\Contact\Service\Exporter\ContactExporterServiceInterface;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractPluginDoctrineBackendController;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\DoctrineListTable;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wonderwp
 * @subpackage Wonderwp/admin
 * @author     Wonderful <jeremy.desvaux@wonderful.fr>
 */
class ContactAdminController extends AbstractPluginDoctrineBackendController
{
    /** @inheritdoc */
    public function getTabs()
    {
        $tabs = [
            1 => ['action' => 'list', 'libelle' => 'Gestion des formulaire'],
            2 => ['action' => 'listFields', 'libelle' => 'Gestion des champs'],
        ];

        return $tabs;
    }

    public function listAction(AbstractListTable $listTableInstance = null)
    {
        /** @var DoctrineListTable $listTableInstance */
        $listTableInstance = $this->manager->getService(ServiceInterface::LIST_TABLE_SERVICE_NAME);
        $listTableInstance->setEntityName(ContactFormEntity::class);
        $listTableInstance->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        parent::listAction($listTableInstance);
    }

    public function editContactFormAction()
    {
        if (!is_env_webpack()) {
            wp_enqueue_script('jquery-ui');
        }
        $modelForm = new ContactFormForm();
        parent::editAction(ContactFormEntity::class, $modelForm);
    }

    public function deleteContactFormAction()
    {
        parent::deleteAction(ContactFormEntity::class);
    }

    public function listFieldsAction()
    {
        $listTable = new ContactFormFieldListTable();
        $listTable->setEntityName(ContactFormFieldEntity::class);
        $listTable->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        parent::listAction($listTable);
    }

    public function editContactFormFieldAction()
    {
        $modelForm = new ContactFormFieldForm();

        parent::editAction(ContactFormFieldEntity::class, $modelForm);
    }

    public function deleteContactFormFieldAction()
    {
        parent::deleteAction(ContactFormFieldEntity::class);
    }

    public function listmsgAction()
    {
        $listTable = $this->manager->getService('msgListTable');
        $listTable->setEntityName($this->manager->getConfig('contactEntityName'));
        $listTable->setTextDomain(WWP_CONTACT_TEXTDOMAIN);

        parent::listAction($listTable);
    }

    public function editContactAction()
    {
        /** @var Container $container */
        $container                  = Container::getInstance();
        $container['wwp.form.view'] = $container->factory(function () use ($container) {
            return new FormViewReadOnly(
                $container['wwp.form.validator']
            );
        });
        $modelForm                  = new ContactForm();
        parent::editAction($this->manager->getConfig('contactEntityName'), $modelForm);
    }

    public function deleteContactAction()
    {
        parent::deleteAction($this->manager->getConfig('contactEntityName'));
    }

    public function exportMsgAction()
    {
        $request = Request::getInstance();
        /** @var Container $container */
        $container = Container::getInstance();
        /** @var ContactManager $manager */
        $manager = $container[WWP_PLUGIN_CONTACT_NAME . '.Manager'];

        /** @var ContactFormRepository $repo */
        $formRepo = $manager->getService('contactFormRepository');
        /** @var ContactRepository $msgRepo */
        $msgRepo = $manager->getService('messageRepository');
        /** @var ContactFormFieldRepository $formFieldRepo */
        $formFieldRepo = $manager->getService('formFieldRepository');

        //Get form item
        $formId            = $request->get('form');
        $contactFormEntity = $formRepo->find($formId);

        if ($contactFormEntity instanceof ContactFormEntity) {

            $records = $msgRepo->findMessagesForExport($contactFormEntity);

            /** @var ContactExporterServiceInterface $exporterService */
            $exporterService = $manager->getService('exporter');
            $exporterService->setFormInstance($contactFormEntity);
            $res = $exporterService->export($records, $formFieldRepo);

        } else {
            $res = new Result(500, ['msg' => 'Form ' . $formId . ' not found']);
        }
        $prefix = $manager->getConfig('prefix');
        $container
            ->offsetGet('wwp.views.baseAdmin')
            ->registerFrags($prefix, [
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.header')),
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.tabs')),
                new VueFrag($manager->getConfig('path.root') . '/admin/pages/export-result.php'),
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.footer')),
            ])
            ->render([
                'title'     => get_admin_page_title(),
                'tabs'      => $this->getTabs(),
                'uploadRes' => $res,
            ]);
    }

    public function emailsAction()
    {
        $container = Container::getInstance();
        $request   = Request::getInstance();
        $prefix    = $this->manager->getConfig('prefix');

        $msgId = $request->query->get('msg');
        /** @var ContactRepository $repository */
        $repository = $this->manager->getService('messageRepository');
        /** @var ContactMailService $mailService */
        $mailService = $this->manager->getService('mail');
        /** @var ContactEntity $contactEntity */
        $contactEntity = $repository->find($msgId);

        $emails         = $mailService->getEmailsFor($contactEntity);
        $sendMailParams = [
            'msg'    => $msgId,
            'page'   => $request->query->get('page'),
            'action' => 'sendMail',
            'locale' => $contactEntity->getLocale()
        ];
        $sendMailLink   = admin_url('/admin.php?' . http_build_query($sendMailParams));

        $container
            ->offsetGet('wwp.views.baseAdmin')
            ->registerFrags($prefix, [
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.header')),
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.tabs')),
                new VueFrag($this->manager->getConfig('path.root') . '/admin/pages/email-inventory.php'),
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.footer')),
            ])
            ->render([
                'title'        => get_admin_page_title(),
                'tabs'         => $this->getTabs(),
                'emails'       => $emails,
                'contact'      => $contactEntity,
                'sendMailLink' => $sendMailLink
            ]);
    }

    public function sendMailAction()
    {
        $container = Container::getInstance();
        $request   = Request::getInstance();
        $prefix    = $this->manager->getConfig('prefix');

        $msgId = $request->query->get('msg');
        /** @var ContactRepository $repository */
        $repository = $this->manager->getService('messageRepository');
        /** @var ContactMailService $mailService */
        $mailService = $this->manager->getService('mail');
        /** @var ContactEntity $contactEntity */
        $contactEntity = $repository->find($msgId);

        $emailRef = $request->query->get('email');
        $email    = $mailService->getEmailFor($emailRef, $contactEntity);
        $result   = $email->send();

        $container
            ->offsetGet('wwp.views.baseAdmin')
            ->registerFrags($prefix, [
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.header')),
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.tabs')),
                new VueFrag($this->manager->getConfig('path.root') . '/admin/pages/email-testsend.php'),
                new VueFrag($container->offsetGet($prefix . '.wwp.path.templates.frags.footer')),
            ])
            ->render([
                'title'    => get_admin_page_title(),
                'tabs'     => $this->getTabs(),
                'emailRef' => $emailRef,
                'email'    => $email,
                'result'   => $result
            ]);
    }
}
