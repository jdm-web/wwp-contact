<?php

namespace WonderWp\Plugin\Contact\ListTable;

use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\DoctrineListTable;

/**
 * Class ContactListTable
 * @package WonderWp\Plugin\Contact
 */
class ContactFormListTable extends DoctrineListTable
{

    /**
     * Compute the columns that are going to be used in the table,
     * if you don\'t want to use them all, just uncomment the foreach, and add to the array the name of all the cols you want to hide.
     * @return array $columns, the array of columns to use with the modules
     */
    function get_columns()
    {
        $cols = parent::get_columns();
        foreach (['data'] as $col) {
            unset($cols[$col]);
        }

        return $cols;
    }

    public function column_action($item, $allowedActions = ['edit', 'delete'], $givenEditParams = [], $givenDeleteParams = [])
    {
        /** @var ContactFormEntity $item */
        $givenEditParams['action']   = 'editContactForm';
        $givenDeleteParams['action'] = 'deleteContactForm';

        parent::column_action($item, $allowedActions, $givenEditParams, $givenDeleteParams);

        if($item->getSaveMsg()) {

            echo ' <a href="' . admin_url('/admin.php?' . http_build_query(
                        [
                            'page'   => $this->request->get('page'),
                            'action' => 'listmsg',
                            'form'   => $item->getId(),
                        ]
                    )) . '" class="list-link">' . __('Liste des messages') . '</a>';
        }
    }

    function extra_tablenav($which, $showAdd = true, $givenEditParams = [])
    {
        $givenEditParams = ['action' => 'editContactForm', 'tab' => 2];
        parent::extra_tablenav($which, $showAdd, $givenEditParams);
    }
}
