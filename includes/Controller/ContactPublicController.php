<?php

namespace WonderWp\Plugin\Contact\Controller;

use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Service\ContactFormService;
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
     */
    public function showFormAction($atts)
    {
        if (empty($atts['form'])) {
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

        $formIds   = explode(',', $atts['form']);
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
                $formDatas[$formId] = $formService->prepareViewParams($formItem, $values);

            }

        }

        $viewService   = wwp_get_theme_service('view');
        $notifications = $viewService->flashesToNotifications('contact');

        return $this->renderView('form', ['formDatas' => $formDatas, 'notifications' => $notifications]);
    }

    public function handleFormAction()
    {
        /** @var ContactFormEntity $formItem */
        /** @var ContactFormService $formService */

        $data         = $this->request->request->all();
        $formItem     = $this->getEntityManager()->find(ContactFormEntity::class, $data['form']);
        $formService  = $this->manager->getService('form');
        $formInstance = $formService->getFormInstanceFromItem($formItem);

        /** @var ContactPersisterService $contactPersisterService */
        $contactPersisterService = $this->manager->getService('persister');
        /** @var ContactHandlerService $contactHandlerService */
        $contactHandlerService = $this->manager->getService('contactHandler');
        $result                = $contactHandlerService->handleSubmit($data, $formInstance, $formItem, $this->container->offsetGet('wwp.form.validator'), $contactPersisterService);
        $msg                   = $result->getCode() === 200 ? trad('mail.sent', WWP_CONTACT_TEXTDOMAIN) : trad('mail.notsent', WWP_CONTACT_TEXTDOMAIN);
        $resdata               = $result->getData();
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
