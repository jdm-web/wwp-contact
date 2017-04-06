<?php

namespace WonderWp\Plugin\Contact;

use WonderWp\Framework\AbstractPlugin\AbstractListTable;

/**
 * Class ContactListTable
 * @package WonderWp\Plugin\Contact
 */
class ContactListTable extends AbstractListTable
{

    /**
     * Compute the columns that are going to be used in the table,
     * if you don\'t want to use them all, just uncomment the foreach, and add to the array the name of all the cols you want to hide.
     * @return array $columns, the array of columns to use with the modules
     */
    function get_columns()
    {
        $cols = parent::get_columns();
        foreach (array('id', 'prenom', 'mail', 'locale', 'sentto') as $col) {
            unset($cols[$col]);
        }
        return $cols;
    }

    function extra_tablenav($which, $showAdd = false, $givenEditParams = array())
    {
        parent::extra_tablenav($which, $showAdd, $givenEditParams);
    }

    /**
     * Message to be displayed when there are no items
     *
     * @since 3.1.0
     * @access public
     */
    public function no_items()
    {
        _e('contact.nomessage.trad', WWP_CONTACT_TEXTDOMAIN);
    }

}
