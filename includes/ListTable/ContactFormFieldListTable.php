<?php

namespace WonderWp\Plugin\Contact\ListTable;

use WonderWp\Plugin\Core\Framework\AbstractPlugin\DoctrineListTable;

class ContactFormFieldListTable extends DoctrineListTable
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
    protected function getItemVal($item, $column_name)
    {
        if ($column_name === 'options') {
            return json_encode($item->getOptions());
        }

        $val = parent::getItemVal($item, $column_name);
        if($column_name=='type'){
            $val = str_replace('\\\\','\\',$val);
        }

        return $val;
    }

    /** @inheritdoc */
    public function column_action($item, $allowedActions = array('edit', 'delete'), $givenEditParams = array(), $givenDeleteParams = array())
    {
        $givenEditParams['action']   = 'editContactFormField';
        $givenEditParams['tab']      = 2;
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
