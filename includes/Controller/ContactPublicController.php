<?php

namespace WonderWp\Plugin\Contact\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Throwable;
use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\PluginSkeleton\Exception\ViewNotFoundException;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Exception\ContactException;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestValidationResult;
use WonderWp\Plugin\Contact\Service\ContactContext;
use WonderWp\Plugin\Contact\Service\ContactNotificationService;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Contact\Service\Form\Post\Processor\WP\ContactFormPostProcessor;
use WonderWp\Plugin\Contact\Service\Form\Post\Validator\WP\ContactFormPostValidator;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestProcessor;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestValidator;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\AbstractPluginDoctrineFrontendController;

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
     * @throws ServiceNotFoundException|\Doctrine\ORM\ORMException
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
                $formDatas[$formId] = $formService->prepareViewParams($formItem, $values);

            }

        }

        /** @var ContactNotificationService $notificationService */
        $notificationService = $this->manager->getService('notification');
        $notifications       = $notificationService->getNotifications();

        $viewParams = [
            'formDatas'     => $formDatas,
            'notifications' => $notifications
        ];

        if (isset($atts['classnames'])) {
            $viewParams['classNames'] = explode(' ', $atts['classnames']);
        }

        return $this->renderView('form', $viewParams);
    }

    public function handleFormAction()
    {
        /** @var ContactFormPostValidator $requestValidator */
        $requestValidator = $this->manager->getService('contactFormPostValidator');
        /** @var  ContactFormPostProcessor $requestProcessor */
        $requestProcessor = $this->manager->getService('contactFormPostProcessor');
        /** @var ContactNotificationService $notificationService */
        $notificationService = $this->manager->getService('notification');

        $request = $this->request;
        if ($request->getMethod() == 'POST') {
            $requestData = $request->request->all();
            if (!empty($requestData['form'])) {
                $requestData['id'] = $requestData['form'];
            }
            $result = $this->validateAndProcessRequest($requestData, $requestValidator, $requestProcessor);
            if ($request->isXmlHttpRequest()) {
                header('Content-Type: application/json');
                echo $result;
                die();
            } else {
                $success = ($result->getCode() == 200);
                $notificationService->addFlash([($success ? 'success' : 'error'), $result->getMsgKey()]);
                $prevPage = get_permalink($request->request->get('post'));
                wp_redirect($prevPage);
                die();
            }
        }

        /*$formItem    = $this->getEntityManager()->find(ContactFormEntity::class, $data['form']);
        $formService = $this->manager->getService('form');
        /** @var ContactFormFieldRepository $contactFormFieldrepository */
        /*$contactFormFieldrepository = $this->manager->getService('formFieldRepository');
        $formInstance               = $formService->fillFormInstanceFromItem($this->container->offsetGet('wwp.form.form'), $formItem, $contactFormFieldrepository);

        /** @var ContactPersisterService $contactPersisterService */
        /*$contactPersisterService = $this->manager->getService('persister');
        /** @var ContactHandlerService $contactHandlerService */
        /*$contactHandlerService = $this->manager->getService('contactHandler');
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
        );*/

        /*//Msg handling based on form result
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
        }*/
    }

    /**
     * @param array $requestParams
     * @param ContactAbstractRequestValidator $requestValidator
     * @param ContactAbstractRequestProcessor $requestProcessor
     * @return AbstractRequestProcessingResult|AbstractRequestValidationResult
     */
    protected function validateAndProcessRequest(
        array                           $requestParams,
        ContactAbstractRequestValidator $requestValidator,
        ContactAbstractRequestProcessor $requestProcessor
    )
    {
        /**
         * Validate request
         */
        $requestParams['origin']  = WP_ENV;
        $requestParams['context'] = ContactContext::FRONT_OFFICE;

        try {
            $requestValidationRes = $requestValidator->validate($requestParams);
        } catch (Throwable $e) {
            $exception            = $e instanceof ContactException ? $e : new ContactException($e->getMessage(), $e->getCode(), $e->getPrevious());
            $requestValidationRes = new $requestValidator::$ResultClass($e->getCode(), $requestParams, $e->getMessage(), [], $exception);
        }
        if ($requestValidationRes->getCode() != 200) {
            return $requestValidationRes;
        }

        /**
         * Process request
         * Request validation Result is then successful, we can procede with the request processing
         */
        /** @var AbstractRequestProcessingResult $requestProcessingRes */
        try {
            $requestProcessingRes = $requestProcessor->process($requestValidationRes);
        } catch (Throwable $e) {
            $exception            = $e instanceof ContactException ? $e : new ContactException($e->getMessage(), $e->getCode(), $e->getPrevious());
            $data                 = [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTrace()
            ];
            $requestProcessingRes = new $requestProcessor::$ResultClass(500, $requestValidationRes, 'fatal.error', $data, $exception);
        }

        /**
         * Return result
         */
        return $requestProcessingRes;
    }
}
