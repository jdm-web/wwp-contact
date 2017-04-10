<?php

namespace WonderWp\Plugin\Contact\ListTable;

use WonderWp\Plugin\Core\Framework\AbstractPlugin\DoctrineListTable;

/**
 * Class ContactListTable
 * @package WonderWp\Plugin\Contact
 */
class ContactFormListTable extends DoctrineListTable{

    /**
     * Compute the columns that are going to be used in the table,
     * if you don\'t want to use them all, just uncomment the foreach, and add to the array the name of all the cols you want to hide.
     * @return array $columns, the array of columns to use with the modules
     */
    function get_columns() {
        $cols = parent::get_columns();
        foreach(array('data') as $col) {
            unset($cols[$col]);
        }
        return $cols;
    }

    public function column_action($item, $allowedActions = array('edit', 'delete'), $givenEditParams = array(), $givenDeleteParams = array())
    {
        $givenEditParams['action'] = 'editContactForm';
        $givenEditParams['tab'] = 2;
        $givenDeleteParams['action'] = 'deleteContactForm';
        parent::column_action($item, $allowedActions, $givenEditParams, $givenDeleteParams);
    }

    function extra_tablenav($which, $showAdd = true, $givenEditParams = array())
    {
        $givenEditParams = array('action'=>'editContactForm','tab'=>2);
        parent::extra_tablenav($which, $showAdd, $givenEditParams);
    }
}
