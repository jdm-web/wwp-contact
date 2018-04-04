<?php

namespace WonderWp\Plugin\Contact\ListTable;

use WonderWp\Component\HttpFoundation\Request;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\DoctrineListTable;
use WonderWp\Plugin\Core\Framework\EntityMapping\EntityAttribute;

/**
 * Class ContactListTable
 * @package WonderWp\Plugin\Contact
 */
class ContactListTable extends DoctrineListTable
{

    private $postIndex = [];

    /**
     * Compute the columns that are going to be used in the table,
     * if you don\'t want to use them all, just uncomment the foreach, and add to the array the name of all the cols you want to hide.
     * @return array $columns, the array of columns to use with the modules
     */
    function get_columns()
    {
        $cols = parent::get_columns();
        foreach (['id', 'updatedAt', 'sentto', 'data', 'action'] as $col) {
            unset($cols[$col]);
        }

        $request  = Request::getInstance();
        $formItem = $this->em->find(ContactFormEntity::class, $request->query->get('form'));
        if ($formItem instanceof ContactFormEntity) {
            $fieldRepo = $this->em->getRepository(ContactFormFieldEntity::class);
            $data      = json_decode($formItem->getData(), true);
            if (!empty($data)) {
                foreach ($data as $fieldId => $fieldOptions) {
                    $field = $fieldRepo->find($fieldId);
                    if ($field instanceof ContactFormFieldEntity) {
                        $cols[$field->getName()] = __($field->getName() . '.trad', $this->getTextDomain());
                    }
                }
            }
        }

        $cols["action"] = __("Actions", $this->textDomain);

        return $cols;
    }

    function prepare_items($filters = [], $orderBy = ['id' => 'DESC'])
    {
        $formItem = $this->em->find(ContactFormEntity::class, Request::getInstance()->query->get('form'));
        $filters = ['form'=>$formItem];
        return parent::prepare_items($filters, $orderBy);
    }

    function extra_tablenav($which, $showAdd = false, $givenEditParams = [])
    {
        parent::extra_tablenav($which, $showAdd, $givenEditParams);
        $request = Request::getInstance();
        echo ' <a href="' . admin_url('/admin.php?' . http_build_query(
                    [
                        'page'   => $request->get('page'),
                        'action' => 'exportMsg',
                        'form'   => $request->get('form'),
                    ]
                )) . '" class="button action export-btn">' . __('Exporter') . '</a>';

    }

    /** @inheritdoc */
    public function column_action($item, $allowedActions = ['edit', 'delete'], $givenEditParams = [], $givenDeleteParams = [])
    {
        $request                     = Request::getInstance();
        $givenEditParams['action']   = 'editContact';
        $givenEditParams['tab']      = 3;
        $givenEditParams['form']     = $request->query->get('form');
        $givenDeleteParams['action'] = 'deleteContact';

        parent::column_action($item, $allowedActions, $givenEditParams, $givenDeleteParams);
    }

    public function column_post($item)
    {
        /** @var ContactEntity $item */
        if (empty($this->postIndex[$item->getPost()])) {
            $this->postIndex[$item->getPost()] = get_the_title($item->getPost());
        }
        echo $this->postIndex[$item->getPost()];
    }

    public function getItemVal($item, $columnName)
    {
        /** @var ContactEntity $item */
        $val = parent::getItemVal($item, $columnName);
        if (empty($val) && !empty($item->getData($columnName))) {
            $val = $item->getData($columnName);
        }
        if(is_string($val)){
            $val = stripslashes($val);
            if(strlen($val)>70) {
                $val = substr($val, 0, 70) . '...';
            }
        }

        return $val;
    }

    /**
     * Message to be displayed when there are no items
     *
     * @since  3.1.0
     * @access public
     */
    public function no_items()
    {
        _e('contact.nomessage.trad', WWP_CONTACT_TEXTDOMAIN);
    }

}
