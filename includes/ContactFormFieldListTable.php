<?php

namespace WonderWp\Plugin\Contact;

use WonderWp\APlugin\ListTable as WwpListTable;

class ContactFormFieldListTable extends WwpListTable
{
    /** @inheritdoc */
    function get_columns()
    {
        $cols = parent::get_columns();

        foreach (array('id', 'data', 'options') as $col) {
            unset($cols[$col]);
        }

        return $cols;
    }

    /** @inheritdoc */
    protected function _getItemVal($item, $column_name)
    {
        if ($column_name === 'options') {
            return json_encode($item->getOptions());
        }

        return parent::_getItemVal($item, $column_name);
    }

    /** @inheritdoc */
    public function column_action($item, $allowedActions = array('edit', 'delete'), $givenEditParams = array(), $givenDeleteParams = array())
    {
        $givenEditParams['action']   = 'editContactFormField';
        $givenEditParams['tab']      = 3;
        $givenDeleteParams['action'] = 'deleteContactFormField';

        parent::column_action($item, $allowedActions, $givenEditParams, $givenDeleteParams);
    }

    /** @inheritdoc */
    function extra_tablenav($which, $showAdd = true, $givenEditParams = array())
    {
        $givenEditParams = array('action' => 'editContactFormField', 'tab' => 3);

        parent::extra_tablenav($which, $showAdd, $givenEditParams);
    }
}
