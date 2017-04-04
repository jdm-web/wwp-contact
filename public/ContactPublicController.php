<?php

namespace WonderWp\Plugin\Contact;

use Doctrine\ORM\EntityManager;
use Respect\Validation\Validator;
use WonderWp\APlugin\AbstractPluginFrontendController;
use WonderWp\DI\Container;
use WonderWp\Forms\Fields\AbstractField;
use WonderWp\Forms\Fields\HiddenField;
use WonderWp\Forms\Fields\SelectField;
use WonderWp\Forms\Form;
use WonderWp\HttpFoundation\Request;
use WonderWp\Theme\ThemeViewService;

class ContactPublicController extends AbstractPluginFrontendController
{
    /** @inheritdoc */
    public function defaultAction($atts)
    {
        return $this->showFormAction($atts);
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
        $formItem      = $this->_entityManager->find(ContactFormEntity::class, $atts['form']);
        $formInstance  = $this->_getFormInstanceFromItem($formItem);
        $viewService   = wwp_get_theme_service('view');
        $notifications = $viewService->flashesToNotifications('contact');
        $opts          = [
            'formStart' => [
                'action' => '/contactFormSubmit',
                'class'  => ['contactForm'],
            ],
            'formEnd'=> [
                'submitLabel'=>__('submit',WWP_CONTACT_TEXTDOMAIN)
            ]
        ];

        return $this->renderView('form', ['formView' => $formInstance->renderView($opts), 'notifications' => $notifications]);
    }

    public function handleFormAction()
    {
        $request      = Request::getInstance();
        $data         = $request->request->all();
        $formItem     = $this->_entityManager->find(ContactFormEntity::class, $data['form']);
        $formInstance = $this->_getFormInstanceFromItem($formItem);

        /** @var ContactHandlerService $contactHandlerService */
        $contactHandlerService = $this->_manager->getService('contactHandler');
        $result                = $contactHandlerService->handleSubmit($data, $formInstance, $formItem);
        $msg                   = $result->getCode() === 200 ? __('mail.sent', WWP_CONTACT_TEXTDOMAIN) : __('mail.notsent', WWP_CONTACT_TEXTDOMAIN);
        $resdata                  = $result->getData();
        $resdata['msg']           = $msg;
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

    /**
     * @param ContactFormEntity $formItem
     *
     * @return Form
     */
    private function _getFormInstanceFromItem($formItem)
    {
        global $post;
        $formInstance = Container::getInstance()->offsetGet('wwp.forms.form');

        // Add configured fields
        $data = json_decode($formItem->getData(), true);
        if (!empty($data)) {
            foreach ($data as $fieldId => $fieldOptions) {
                $field = $this->_generateDefaultField($fieldId, $fieldOptions);
                $formInstance->addField($field);
            }
        }

        // Add other necessary field
        $f = new HiddenField('form', $formItem->getId());
        $formInstance->addField($f);

        $f = new HiddenField('post', $post->ID);
        $formInstance->addField($f);

        return $formInstance;
    }

    /**
     * @param string $fieldId
     * @param array  $fieldOptions
     *
     * @return null|AbstractField
     */
    private function _generateDefaultField($fieldId, $fieldOptions)
    {
        /** @var EntityManager $em */
        $em    = Container::getInstance()->offsetGet('entityManager');
        $field = $em->getRepository(ContactFormFieldEntity::class)->find($fieldId);

        if (!$field instanceof ContactFormFieldEntity) {
            return null;
        }

        $label           = __($field->getName() . '.trad', WWP_CONTACT_TEXTDOMAIN);
        $displayRules    = [
            'label' => $label,
        ];
        $validationRules = [];

        if ($field->isRequired($fieldOptions)) {
            $validationRules[] = Validator::notEmpty();
        }

        $fieldClass    = $field->getType();
        $fieldInstance = new $fieldClass($field->getName(), null, $displayRules, $validationRules);

        if ($fieldInstance instanceof SelectField) {
            $currentLocale = get_locale();
            $choices       = ['' => __('choose.subject.trad', WWP_CONTACT_TEXTDOMAIN)];
            foreach ($field->getOption('choices', []) as $choice) {
                if ($choice['locale'] === $currentLocale) {
                    $choices[$choice['value']] = $choice['label'];
                }
            }
            $fieldInstance->setOptions($choices);
        }

        return $fieldInstance;
    }
}
