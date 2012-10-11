<?php

class Application_Model_UserChatMapper {

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
            $this->setDbTable('Application_Model_DbTable_UserChat');
        }
        return $this->_dbTable;
    }

    public function savemessage(Application_Model_UserChat $message) {
        $data = array(
            'message' => $message->getMessage(),
            'user_id' => $message->getUser_id(),
            'date' => date('Y-m-d H:i:s'),
        );

        if (null === ($id = $message->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll('id','date DESC',50,0);
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_UserChat();
            $entry->setId($row->id)
                    ->setUser_id($row->user_id)
                    ->setMessage($row->message)
                    ->setDate($row->date);
            $entries[] = $entry;
        }
        return $entries;
    }

}