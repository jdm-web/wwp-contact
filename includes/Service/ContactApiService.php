<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\API\AbstractApiService;
use WonderWp\Component\API\Annotation\WPApiEndpoint;
use WonderWp\Component\API\Annotation\WPApiNamespace;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
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
    public function form(WP_REST_Request $request)
    {
        $formId = $request->get_param('id');
        if (empty($formId)) {
            return new WP_REST_Response([
                'Missing required param : id'
            ], 500);
        }

        //load form item
        $em       = EntityManager::getInstance();
        $formItem = $em->find(ContactFormEntity::class, $formId);
        if (empty($formItem)) {
            return new WP_REST_Response([
                sprintf('Form not found : %d', $formId)
            ], 404);
        }

        $readResult = $this->serializer->unserialize($formItem);

        return new WP_REST_Response(
            $readResult->getData(),
            $readResult->getCode()
        );
    }

}
