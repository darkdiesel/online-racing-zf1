<?php

class Application_Model_UserRoleMapper {

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
            $this->setDbTable('Application_Model_DbTable_UserRole');
        }
        return $this->_dbTable;
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_UserRole();
            $entry->setId($row->id)
                    ->setName($row->name);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function getRoleName($id) {
        $result = $this->getDbTable()->fetchRow(array('id = ?' => $id));
        return $result->name;
    }

}