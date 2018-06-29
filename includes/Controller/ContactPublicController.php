<?php

namespace WonderWp\Plugin\Contact\Controller;

use WonderWp\Framework\HttpFoundation\Request;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Service\ContactFormService;
use WonderWp\Plugin\Contact\Service\ContactHandlerService;
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
        $testGetValues = Request::getInstance()->get('values');
        if(!empty($testGetValues)){
            $values = $testGetValues;
        } elseif(!empty($atts['values'])){
            parse_str($atts['values'], $values);
        } else {
            $values = [];
        }

        $formItem      = $this->getEntityManager()->find(ContactFormEntity::class, $atts['form']);
        $formService   = $this->manager->getService('form');

        if(!empty($formItem)) {
            $formInstance = $formService->getFormInstanceFromItem($formItem, $values);
            $formInstance->setName('contactForm');
            $formView = $formService->getViewFromFormInstance($formInstance);
        } else {
            $request      = Request::getInstance();
            $request->getSession()->getFlashbag()->add('contact', ['error', trad('form.not.found',WWP_CONTACT_TEXTDOMAIN).' ['.$atts['form'].']']);
            $formView = null;
        }

        $viewService   = wwp_get_theme_service('view');
        $notifications = $viewService->flashesToNotifications('contact');
        $opts          = [
            'formStart' => [
                'action' => '/contactFormSubmit'
            ],
            'formEnd'   => [
                'submitLabel' => __('submit', WWP_CONTACT_TEXTDOMAIN),
            ],
        ];

        if(!empty($formItem)) {

            // Text intro
            $introTrad = $formService->getTranslation($formItem->getId(), 'form', 'intro', false, true);

            if (false === $introTrad && current_user_can('manage_options')) {
                $introTrad = "<span class=\"help\">Message pour l'administrateur : le texte d'intro du formulaire peut être administré via les clés : <strong>form." . $formItem->getId() . ".intro.trad</strong> ou <strong>form.intro.trad</strong>.</span>";
            }

            if (false !== $introTrad) {
                $opts['formBeforeFields'][] = wp_sprintf($introTrad, $formItem->getNumberOfDaysBeforeRemove());
            }

        }

        return $this->renderView('form', ['formView' => $formView, 'formViewOpts' => $opts, 'notifications' => $notifications, 'formItem' => $formItem]);
    }

    public function handleFormAction()
    {
        /** @var ContactFormEntity $formItem */
        /** @var ContactFormService $formService */

        $request      = Request::getInstance();
        $data         = $request->request->all();
        $formItem     = $this->getEntityManager()->find(ContactFormEntity::class, $data['form']);
        $formService  = $this->manager->getService('form');
        $formInstance = $formService->getFormInstanceFromItem($formItem);

        /** @var ContactHandlerService $contactHandlerService */
        $contactHandlerService = $this->manager->getService('contactHandler');
        $result                = $contactHandlerService->handleSubmit($data, $formInstance, $formItem);
        $msg                   = $result->getCode() === 200 ? __('mail.sent', WWP_CONTACT_TEXTDOMAIN) : __('mail.notsent', WWP_CONTACT_TEXTDOMAIN);
        $resdata               = $result->getData();
        $resdata['msg']        = $msg;
        $result->setData($resdata);

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json');
            echo $result;
            die();
        } else {
            $prevPage = get_permalink($data['post']);
            $request->getSession()->getFlashbag()->add('contact', [($result->getCode() === 200 ? 'success' : 'error'), $result->getData('msg')]);
            wp_redirect($prevPage);
            die();
        }
    }
}
