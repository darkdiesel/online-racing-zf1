<?php

class Application_Model_LeagueMapper {

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
            $this->setDbTable('Application_Model_DbTable_League');
        }
        return $this->_dbTable;
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_League();
            $entry->setId($row->id)
                    ->setName($row->name)
                    ->setLogo($row->logo)
                    ->setDescription($row->description)
                    ->setDate_create($row->date_create)
                    ->setDate_edit($row->date_edit);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function save(Application_Model_League $league, $action) {
        switch ($action) {
            case 'add':
                $date = date('Y-m-d H:i:s');
                $data = array(
                    'name' => $league->getName(),
                    'logo' => $league->getLogo(),
                    'description' => $league->getDescription(),
                    'date_create' => $date,
                    'date_edit' => $date,
                );
                break;
            case 'edit':
                $data = array(
                    'id' => $league->getId(),
                    'name' => $league->getName(),
                    'logo' => $league->getLogo(),
                    'description' => $league->getDescription(),
                    'date_edit' => date('Y-m-d H:i:s')
                );
                break;
            default:
                $data = array();
                break;
        }

        if (null === ($id = $league->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    public function getLeagueDataById($id, $action) {
        switch ($action) {
            case 'view':
                $select = $this->getDbTable()
                        ->select()
                        ->from(array('a_t' => 'league'), 'id')
                        ->where('a_t.id = ?', $id)
                        ->columns(array('id', 'name', 'logo', 'description', 'date_create', 'date_edit'));
                break;
            case 'edit':
                $select = $this->getDbTable()
                        ->select()
                        ->from(array('a_t' => 'league'), 'id')
                        ->where('a_t.id = ?', $id)
                        ->columns(array('id', 'name', 'logo', 'description', 'date_create', 'date_edit'));
            default:

                break;
        }

        $result = $this->getDbTable()
                ->fetchRow($select);

        if (0 == count($result)) {
            return 'null';
        }

        return $result;
    }

    public function getLeaguesPager($count, $page, $page_range, $action, $order) {
        switch ($action) {
            case 'all':
                $adapter = new Zend_Paginator_Adapter_DbTableSelect($this->getDbTable()
                                        ->select()
                                        ->from(array('a_t' => 'league'), 'id')
                                        ->columns(array('id', 'name', 'logo', 'description', 'date', 'date_edit'))
                                        ->order('id ' . $order));
                break;
            case 'admin_all':
                $adapter = new Zend_Paginator_Adapter_DbTableSelect($this->getDbTable()
                                        ->select()
                                        ->from(array('a_t' => 'league'), 'id')
                                        ->columns(array('id', 'name', 'logo', 'description', 'date_create', 'date_edit'))
                                        ->order('id ' . $order));
                break;
        }

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

}