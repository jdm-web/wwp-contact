<?php

namespace WonderWp\Plugin\Contact\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ContactNotificationService
{
    const namespace = 'contact';

    /** @var SessionInterface */
    protected $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }


    public function getFlashes()
    {
        return $this->session->getFlashBag()->get(static::namespace);
    }

    public function addFlash(array $flash)
    {
        $this->session->getFlashbag()->add(static::namespace, $flash);
    }

    public function getNotifications()
    {
        $flashes = $this->getFlashes();

        $rawNotifications = [];

        if (!empty($flashes)) {
            foreach ($flashes as $flash) {
                $rawNotifications[] = '<div>' . $flash[0] . ' : ' . $flash[1] . '</div>';
            }
        }

        return apply_filters('theme_flash_notifications', $rawNotifications, $flashes);
    }

    public function createNotificationComponent($type, $message)
    {
        /** @var \WonderWp\Theme\Core\Service\ThemeViewService $viewService */
        $viewService = wwp_get_theme_service('view');
        return $viewService->createNotificationComponent($type, $message);
    }
}
