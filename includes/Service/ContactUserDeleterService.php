<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Plugin\Contact\Entity\ContactEntity;

class ContactUserDeleterService
{

    /**
     * Hook exectued to display some relevant markup on the screen before confirming a user deletion
     *
     * @param       $currentUser
     * @param array $userIds
     */
    public function deleteUserForm($currentUser, array $userIds)
    {
        if (!empty($userIds)) {
            $mk = '
            <h3>' . __('Contact', WWP_CONTACT_TEXTDOMAIN) . '</h3>
            <p>Les messages de contact sauvegardés via l\'adresse mail de ces utilisateurs vont être supprimé de la base de données</p>';

            echo $mk;
        }
    }

    /**
     * Executed just before the user is going to be deleted
     *
     * @param int $userId , the WordPress user id
     *
     * @return Result
     */
    public function onUserBeforeDelete($userId)
    {
        $userData = get_userdata($userId);
        if (!empty($userData) && !empty($userData->user_email)) {
            $queryRes = $this->deleteMessagesFor($userData->user_email);

            return new Result(200, ['msg' => 'Contact ' . $userId . ' deleted from contact plugin', 'res' => $queryRes]);
        } else {
            return new Result(500, ['msg' => 'No mail found for user id ' . $userId]);
        }

    }

    /**
     * Delte the contact messages sent by a particular email address (and for a particular contact form id)
     *
     * @param string $userMail
     * @param int    $formId
     *
     * @return false|int
     */
    public function deleteMessagesFor($userMail, $formId = null)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $query = 'DELETE FROM ' . $wpdb->prefix . 'contact WHERE data LIKE "%' . $userMail . '%"';
        if (!empty($formId)) {
            $query .= ' AND form_id = ' . (int)$formId;
        }

        return $wpdb->query($query);
    }

    /**
     * Remove an array of ContactEnties
     *
     * @param ContactEntity[] $contactEntities
     */
    public function removeContactEntities(array $contactEntities)
    {
        // Get container
        $container  = Container::getInstance();
        $em         = $container->offsetGet('entityManager');
        $siteUrl    = get_bloginfo('url');
        $uploadDirs = wp_upload_dir();
        $baseDir    = $uploadDirs['basedir'];

        // Remove them
        if (count($contactEntities) > 0) {
            foreach ($contactEntities as $contactEntity) {

                //Remove associated files
                $datas = $contactEntity->getData();
                if (!empty($datas)) {
                    foreach ($datas as $key => $val) {
                        if (strpos($val, '/app/uploads/contact') !== false) {
                            $path = $baseDir . (str_replace(['/app/uploads', $siteUrl], '', $val));
                            if (file_exists($path)) {
                                unlink($path);
                            }
                        }
                    }
                }
                $em->remove($contactEntity);
            }
            $em->flush();
        }
    }
}
