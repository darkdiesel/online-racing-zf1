<?php

class Application_Model_GameMapper {

    protected $_dbTable;

    public function setDbTable($dbTable) {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable() {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Game');
        }
        return $this->_dbTable;
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Game();
            $entry->setId($row->id)
                    ->setName($row->name)
                    ->setArticle_Id($row->article_id);
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function save(Application_Model_Game $game, $action) {
        switch ($action) {
            case 'add':
                $date = date('Y-m-d H:i:s');
                $data = array(
                    'name' => $game->getName(),
                );
                break;
            case 'edit':
                $data = array(
                    'id' => $game->getId(),
                    'name' => $game->getName(),
                );
                break;
            default:
                $data = array();
                break;
        }

        if (null === ($id = $game->getId())) {
            unset($data['id']);
            return $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

}