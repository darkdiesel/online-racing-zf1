<?php

class Application_Model_ArticleTypeMapper {

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
            $this->setDbTable('Application_Model_DbTable_ArticleType');
        }
        return $this->_dbTable;
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_ArticleType();
            $entry->setId($row->id)
                    ->setName($row->name);
            $entries[] = $entry;
        }
        return $entries;
    }

    public function save(Application_Model_ArticleType $article_type, $action) {
        switch ($action) {
            case 'add':
                $data = array(
                    'name' => $article_type->getName(),
                    'description' => $article_type->getDescription()
                );
                break;
            case 'edit':
                $data = array(
                    'id' => $article_type->getId(),
                    'name' => $article_type->getName(),
                    'description' => $article_type->getDescription()
                );
                break;
            default:
                $data = array();
                break;
        }

        if (null === ($id = $article_type->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    public function getArticleTypeDataById($id, $action) {
        switch ($action) {
            case 'view':
                $select = $this->getDbTable()
                        ->select()
                        ->from(array('a_t' => 'article_type'), 'id')
                        ->where('a_t.id = ?', $id)
                        ->columns(array('id', 'name', 'description'));
                break;
            case 'edit':
                $select = $this->getDbTable()
                        ->select()
                        ->from(array('a_t' => 'article_type'), 'id')
                        ->where('a_t.id = ?', $id)
                        ->columns(array('id', 'name', 'description'));
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

    public function getArticleTypesPager($count, $page, $page_range, $action, $order) {
        switch ($action) {
            case 'all':
                $adapter = new Zend_Paginator_Adapter_DbTableSelect($this->getDbTable()
                                        ->select()
                                        ->from(array('a_t' => 'article_type'), 'id')
                                        ->columns(array('id', 'name', 'description'))
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