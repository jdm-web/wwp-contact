<?php

namespace WonderWp\Plugin\Contact\Service;

use WonderWp\Component\API\Result;

class ContactUserDeleterService
{

    /**
     * Hook exectued to display some relevant markup on the screen before confirming a user deletion
     * @param $currentUser
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
     * @param string $userMail
     * @param int $formId
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
}
