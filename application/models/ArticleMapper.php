<?php

class Application_Model_ArticleMapper {

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
            $entry = new Application_Model_Article();
            $entry->setId($row->id)
                    ->setUser_id($row->user_id)
                    ->setTitle($row->title)
                    ->setText($row->text)
                    ->setDate($row->date)
                    ->setViews($row->views)
                    ->setLast_ip($row->last_ip);
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function save(Application_Model_Article $article, $action) {
        switch ($action) {
            case 'add':
                $date = date('Y-m-d H:i:s');
                $data = array(
                    'user_id' => $article->getUser_id(),
                    'title' => $article->getTitle(),
                    'text' => $article->getText(),
                    'date' => $date,
                    'date_edit' => $date,
                    'views' => 0,
                    'last_ip' => 'my'
                );
                break;
            case 'edit':
                $data = array(
                    'title' => $article->getTitle(),
                    'text' => $article->getText(),
                    'date_edit' => date('Y-m-d H:i:s')
                );
                break;
            case 'inc_views':
                $data = array(
                    'views' => $article->getViews()
                );
                break;
            default:
                $data = array(
                );
                break;
        }

        if (null === ($id = $article->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    
    }
    
     public function getArticlesPager($count, $page, $page_range){
        $adapter = new Zend_Paginator_Adapter_DbSelect($this->getDbTable()->select()->from('article')->order('id ASC'));
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);
        
        return $paginator;
    }
    
    /*
     * Uses for controller: user; action: info
     */

    public function getArticleDataById($id) {
        $result = $this->getDbTable()->fetchRow('id = "' . $id . '"');
        if (0 == count($result)) {
            return 'null';
        }

        $entry = new Application_Model_Article();

        $entry->setId($result->id);
        $entry->setTitle($result->title);
        $entry->setText($result->text);
        $entry->setdate($result->date);
        $entry->setDate_edit($result->date_edit);
        $entry->setViews($result->views);
        $entry->setLast_ip($result->last_ip);

        return $entry;
    }

}