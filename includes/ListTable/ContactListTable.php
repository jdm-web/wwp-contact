<?php

namespace WonderWp\Plugin\Contact\ListTable;

use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormEntity;
use WonderWp\Plugin\Contact\Entity\ContactFormFieldEntity;
use WonderWp\Plugin\Contact\Repository\ContactFormFieldRepository;
use WonderWp\Plugin\Contact\Service\Form\ContactFormService;
use WonderWp\Plugin\Core\Framework\AbstractPlugin\DoctrineListTable;

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

        $formItem = $this->em->find(ContactFormEntity::class, $this->request->query->get('form'));
        if ($formItem instanceof ContactFormEntity) {
            /** @var ContactFormFieldRepository $fieldRepo */
            $fieldRepo        = $this->em->getRepository(ContactFormFieldEntity::class);
            $configuredFields = json_decode($formItem->getData(), true);
            if (!empty($configuredFields)) {

                //traitement par groupe, si on a des infos de groupes dans le champ data et si on a plus d'un groupe de champs
                if (isset($configuredFields["fields"]) && isset($configuredFields["groups"]) && count($configuredFields["groups"]) > 1) {
                    //recupère tous les champs de chaque groupe
                    foreach ($configuredFields["fields"] as $fieldId => $fieldOptions) {
                        $this->addFieldToColumns($fieldId, $fieldRepo, $formItem,$cols);
                    }
                } else {
                    //si on a un seul groupe, on recupere les champs => pas de gestion de la notion de groupe
                    if (isset($configuredFields["fields"])) {
                        $configuredFields = $configuredFields["fields"];
                    }

                    foreach ($configuredFields as $fieldId => $fieldOptions) {
                        //Add to inventory
                        $this->addFieldToColumns($fieldId, $fieldRepo, $formItem,$cols);
                    }
                }
            }
        }

        $cols["action"] = __("Actions", $this->textDomain);

        return $cols;
    }

    protected function addFieldToColumns($fieldId, ContactFormFieldRepository $fieldRepo, ContactFormEntity $formItem,array &$cols)
    {
        $heading = '';
        $field   = $fieldRepo->find($fieldId);
        if ($field instanceof ContactFormFieldEntity) {
            $heading = ContactFormService::getTranslation($formItem->getId(), $field->getName());
            if (strlen($heading) > 70) {
                $heading = substr($heading, 0, 70) . '...';
            }
        }

        $cols[$field->getName()] = $heading;
    }

    function prepare_items($filters = [], $orderBy = ['id' => 'DESC'])
    {
        $formItem = $this->em->find(ContactFormEntity::class, $this->request->query->get('form'));
        $filters  = ['form' => $formItem];

        return parent::prepare_items($filters, $orderBy);
    }

    function extra_tablenav($which, $showAdd = false, $givenEditParams = [])
    {
        parent::extra_tablenav($which, $showAdd, $givenEditParams);
        echo ' <a href="' . admin_url('/admin.php?' . http_build_query(
                    [
                        'page'   => $this->request->get('page'),
                        'action' => 'exportMsg',
                        'form'   => $this->request->get('form'),
                    ]
                )) . '" class="button action export-btn">' . __('Exporter') . '</a>';

    }

    /** @inheritdoc */
    public function column_action($item, $allowedActions = ['edit', 'delete'], $givenEditParams = [], $givenDeleteParams = [])
    {
        $givenEditParams['action']            = 'editContact';
        $givenEditParams['tab']               = 3;
        $givenEditParams['form']              = $this->request->query->get('form');
        $givenDeleteParams['action']          = 'deleteContact';
        $givenDeleteParams['redirect_action'] = 'listmsg';

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
        if (is_string($val)) {
            $val = stripslashes($val);
            if (strlen($val) > 70) {
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
