<?php

namespace WonderWp\Plugin\Contact\Service;

use Throwable;
use WonderWp\Component\API\AbstractApiService;
use WonderWp\Component\API\Annotation\WPApiEndpoint;
use WonderWp\Component\API\Annotation\WPApiNamespace;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Mailing\Gateways\FakeMailer;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Exception\ClassNotFoundException;
use WonderWp\Plugin\Contact\Exception\ContactException;
use WonderWp\Plugin\Contact\Result\AbstractResult\AbstractRequestProcessingResult;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestProcessor;
use WonderWp\Plugin\Contact\Service\Request\ContactAbstractRequestValidator;
use WonderWp\Plugin\Contact\Service\Serializer\ContactSerializerInterface;
use WonderWp\Plugin\Core\Framework\Doctrine\EntityManager;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @WPApiNamespace(
 *     namespace="wwp-contact"
 * )
 */
class ContactApiService extends AbstractApiService
{
    /** @var ContactSerializerInterface */
    protected $serializer;

    /**
     * @inheritDoc
     */
    public function __construct(AbstractManager $manager = null, ContactSerializerInterface $serializer)
    {
        parent::__construct($manager);
        $this->serializer = $serializer;
    }

    /**
     * @WPApiEndpoint(
     *     url = "/form/(?P<id>[\d]+)",
     *     args = {
     *       "methods": "GET"
     *     }
     * )
     * Available at :
     * - /wp-json/wwp-contact/v1/form/<formid>
     */
    public function form_read(WP_REST_Request $request)
    {
        /** @var ContactAbstractRequestValidator $requestValidator */
        $requestValidator = $this->manager->getService('contactFormReadValidator');
        /** @var ContactAbstractRequestProcessor $requestProcessor */
        $requestProcessor = $this->manager->getService('contactFormReadProcessor');

        return $this->validateAndProcessRequest(
            $request,
            $requestValidator,
            $requestProcessor
        );
    }

    /**
     * @WPApiEndpoint(
     *     url = "/form/(?P<id>[\d]+)",
     *     args = {
     *       "methods": "POST"
     *     }
     * )
     * Available at :
     * - /wp-json/wwp-contact/v1/form/<formid>
     */
    public function form_post(WP_REST_Request $request)
    {
        /** @var ContactAbstractRequestValidator $requestValidator */
        $requestValidator = $this->manager->getService('contactFormPostValidator');
        /** @var ContactAbstractRequestProcessor $requestProcessor */
        $requestProcessor = $this->manager->getService('contactFormPostProcessor');
        return $this->validateAndProcessRequest(
            $request,
            $requestValidator,
            $requestProcessor
        );
    }

    protected function validateAndProcessRequest(
        WP_REST_Request                 $request,
        ContactAbstractRequestValidator $requestValidator,
        ContactAbstractRequestProcessor $requestProcessor
    )
    {
        /**
         * Validate request
         */
        $requestParams                         = $request->get_params();
        $requestFiles                          = $request->get_file_params();
        $requestParams['origin']               = !empty($request->get_header('origin')) ? $request->get_header('origin') : WP_ENV;
        $requestParams['context']              = ContactContext::API;
        $isIntegrationTesting                  = ContactTestDetector::isIntegrationTesting($requestParams['origin']);
        $requestParams['isIntegrationTesting'] = $isIntegrationTesting;
        if ($isIntegrationTesting) {
            $this->handleIntegrationTesting();
        }

        try {
            $requestValidationRes = $requestValidator->validate($requestParams,$requestFiles);
        } catch (Throwable $e) {
            $exception = $e instanceof ContactException ? $e : new ContactException($e->getMessage(), $e->getCode(), $e->getPrevious());
            if (empty($requestValidator::$ResultClass)) {
                throw new ClassNotFoundException(get_class($requestValidator) . '::$ResultClass');
            }
            $requestValidationRes = new $requestValidator::$ResultClass($e->getCode(), $requestParams, $e->getMessage(), [], $exception);
        }
        if ($requestValidationRes->getCode() != 200) {
            return new WP_REST_Response($requestValidationRes->toArray(), $requestValidationRes->getCode());
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
        $responseArray = $requestProcessingRes->toArray();
        if ($requestProcessingRes->getCode() === 200 && isset($responseArray['validationResult'])) {
            //If request is successful, it's unlikely we'll need the validation result in the response, so we clean it up.
            unset($responseArray['validationResult']);
        }
        return new WP_REST_Response($responseArray, $requestProcessingRes->getCode());
    }

    protected function handleIntegrationTesting()
    {
        $container                    = Container::getInstance();
        $container['RgpdMailerClass'] = $container->factory(function () {
            return new FakeMailer();
        });
    }

}
