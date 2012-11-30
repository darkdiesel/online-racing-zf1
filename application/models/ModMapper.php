<?php

class Application_Model_RoleMod {

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
            $this->setDbTable('Application_Model_DbTable_Article');
        }
        return $this->_dbTable;
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_Mod();
            $entry->setId($row->id)
                    ->setGameId($row->game_id)
                    ->setName($row->name)
                    ->setDeveloper($row->developer)
                    ->setYear($row->year)
                    ->setDescription($row->description)
                    ->setArticle_Id($row->article_id);
            $entries[] = $entry;
        }
        return $entries;
    }

}