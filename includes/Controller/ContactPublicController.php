<?php

namespace WonderWp\Plugin\Contact\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\PluginSkeleton\Exception\ViewNotFoundException;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
use WonderWp\Plugin\Contact\Service\ContactPersisterService;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractPluginDoctrineFrontendController;
use WonderWp\Theme\Core\Service\ThemeViewService;

class ContactPublicController extends AbstractPluginDoctrineFrontendController
{
    /** @inheritdoc */
    public function defaultAction(array $attributes = [])
    {
        return $this->showFormAction($attributes);
    }

    /**
     * @param array $atts
     *
     * @return bool|string
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ViewNotFoundException
     * @throws ServiceNotFoundException
     */
    public function showFormAction($atts)
    {
        //Retro compat old form attribute :
        if (!empty($atts['form'])) {
            $atts['form__'] = $atts['form'];
        }

        if (empty($atts['form__'])) {
            return false;
        }

        /** @var ThemeViewService $viewService */
        /** @var ContactFormEntity $formItem */
        /** @var ContactFormService $formService */

        //Check if some values have been passed to the form
        $testGetValues = $this->request->get('values');
        if (!empty($testGetValues)) {
            $values = $testGetValues;
        } elseif (!empty($atts['values'])) {
            parse_str($atts['values'], $values);
        } else {
            $values = [];
        }

        $formIds   = explode(',', $atts['form__']);
        $formDatas = [];

        if (!empty($formIds)) {

            /** @var ContactFormService $formService */
            $formService = $this->manager->getService('form');

            foreach ($formIds as $formId) {

                $formItem = $this->getEntityManager()->find(ContactFormEntity::class, $formId);
                if (empty($formItem)) {
                    $request = Request::getInstance();
                    $request->getSession()->getFlashbag()->add('contact', ['error', trad('form.not.found', WWP_CONTACT_TEXTDOMAIN) . ' [' . $atts['form'] . ']']);
                }
                $formDatas[$formId] = $formService->prepareViewParams($formItem, $values, $this->request);

            }

        }

        $viewService   = wwp_get_theme_service('view');
        $notifications = $viewService->flashesToNotifications('contact');

        $viewParams = [
            'formDatas'     => $formDatas,
            'notifications' => $notifications
        ];

        if (isset($atts['classnames'])) {
            $viewParams['classNames'] = explode(' ', $atts['classnames']);
        }

        return $this->renderView('form', $viewParams);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function handleFormAction()
    {
        /** @var ContactFormEntity $formItem */
        /** @var ContactFormService $formService */

        $data = $this->request->request->all();

        $formItem    = $this->getEntityManager()->find(ContactFormEntity::class, $data['form']);
        $formService = $this->manager->getService('form');
        /** @var ContactFormFieldRepository $contactFormFieldrepository */
        $contactFormFieldrepository = $this->manager->getService('formFieldRepository');
        $formInstance               = $formService->fillFormInstanceFromItem($this->container->offsetGet('wwp.form.form'), $formItem, $contactFormFieldrepository);

        /** @var ContactPersisterService $contactPersisterService */
        $contactPersisterService = $this->manager->getService('persister');
        /** @var ContactHandlerService $contactHandlerService */
        $contactHandlerService = $this->manager->getService('contactHandler');
        $contactEntityName     = $this->manager->getConfig('contactEntityName');
        $translationDomain     = $this->manager->getConfig('validator.translationDomain');
        $result                = $contactHandlerService->handleSubmit(
            $data,
            $formInstance,
            $formItem,
            $this->container->offsetGet('wwp.form.validator'),
            $contactPersisterService,
            $contactEntityName,
            $translationDomain
        );

        //Msg handling based on form result
        if ($result->getCode() === 200) {
            $formSpecificKey  = 'form-' . $formItem->getId() . '.mail.sent';
            $formSpecificTrad = __($formSpecificKey, WWP_CONTACT_TEXTDOMAIN);
            $msg              = $formSpecificTrad !== $formSpecificKey ? $formSpecificTrad : trad('mail.sent', WWP_CONTACT_TEXTDOMAIN);
        } else {
            $formSpecificKey  = 'form-' . $formItem->getId() . '.mail.notsent';
            $formSpecificTrad = __($formSpecificKey, WWP_CONTACT_TEXTDOMAIN);
            $msg              = $formSpecificTrad !== $formSpecificKey ? $formSpecificTrad : trad('mail.notsent', WWP_CONTACT_TEXTDOMAIN);
        }

        $resdata = $result->getData();
        if (isset($resdata['msg'])) {
            $resdata['original-msg'] = $resdata['msg'];
        }
        $resdata['msg'] = $msg;
        $result->setData($resdata);

        $result = apply_filters('contact.handleformaction.result', $result, $data, $formItem);

        if ($this->request->isXmlHttpRequest()) {
            header('Content-Type: application/json');
            echo $result;
            die();
        } else {
            $prevPage = get_permalink($data['post']);
            $this->request->getSession()->getFlashbag()->add('contact', [($result->getCode() === 200 ? 'success' : 'error'), $result->getData('msg')]);
            wp_redirect($prevPage);
            die();
        }
    }
}
