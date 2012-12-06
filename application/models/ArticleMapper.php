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

    /*
     * Uses for controller: article; action: add
     */

    public function save(Application_Model_Article $article, $action) {
        switch ($action) {
            case 'add':
                $date = date('Y-m-d H:i:s');
                $data = array(
                    'user_id' => $article->getUser_id(),
                    'article_type_id' => $article->getArticle_Type_id(),
                    'content_type_id' => $article->getContent_Type_id(),
                    'title' => $article->getTitle(),
                    'text' => $article->getText(),
                    'image' => $article->getimage(),
                    'date' => $date,
                    'date_edit' => $date,
                    'views' => 0,
                    'publish' => $article->getPublish(),
                    'last_ip' => ''
                );
                break;
            case 'edit':
                $data = array(
                    'id' => $article->getId(),
                    'title' => $article->getTitle(),
                    'text' => $article->getText(),
                    'image' => $article->getimage(),
                    'publish' => $article->getPublish(),
                    'date_edit' => date('Y-m-d H:i:s')
                );
                break;
            default:
                $data = array(
                );
                break;
        }

        if (null === ($id = $article->getId())) {
            unset($data['id']);
            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /*
     * Uses for controller: article, admin; action: view, articles
     */

    public function getArticlesPager($count, $page, $page_range, $article_type, $action, $order) {
        switch ($action) {
            case 'news':
                $adapter = new Zend_Paginator_Adapter_DbTableSelect($this->getDbTable()
                                        ->select()
                                        ->from('article')
                                        //->join('user', 'user.id = article.user_id')
                                        ->where('publish=1 and article_type_id=' . $article_type)
                                        ->order('id '.$order));
                break;
            case 'all':
                $adapter = new Zend_Paginator_Adapter_DbTableSelect($this->getDbTable()
                                        ->select()
                                        ->setIntegrityCheck(false)
                                        ->from(array('a' => 'article'), 'id')
                                        ->join(array('u' => 'user'), 'a.user_id = u.id', 'login')
                                        ->columns(array('id', 'user_id', 'title', 'text', 'image', 'views', 'date', 'date_edit'))
                                        ->where('publish=1')
                                        ->order('id '.$order));
                break;
            case 'admin_all':
                $adapter = new Zend_Paginator_Adapter_DbTableSelect($this->getDbTable()
                                        ->select()
                                        ->setIntegrityCheck(false)
                                        ->from(array('a' => 'article'), 'id')
                                        ->join(array('u' => 'user'), 'a.user_id = u.id', 'login')
                                        ->join(array('at' => 'article_type'), 'a.article_type_id = at.id', 'name')
                                        ->columns(array('user_id', 'title', 'text', 'image', 'views', 'date', 'date_edit', 'article_type_id'))
                                        ->order('id '.$order));
                break;
            default:

                break;
        }

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    /*
     * Uses for controller: article; action: view
     */

    public function getArticleDataById($id, $action) {
        switch ($action) {
            case 'view':
                $select = $this->getDbTable()
                        ->select()
                        ->setIntegrityCheck(false)
                        ->from(array('a' => 'article'), 'id')
                        ->where('a.id = ? and a.publish = 1', $id)
                        ->join(array('u' => 'user'), 'a.user_id = u.id', 'login')
                        ->columns(array('user_id', 'title', 'text', 'image', 'views', 'date', 'date_edit', 'article_type_id', 'last_ip', 'content_type_id', 'publish'));
                break;
            case 'edit':
                $select = $this->getDbTable()
                        ->select()
                        ->setIntegrityCheck(false)
                        ->from(array('a' => 'article'), 'id')
                        ->where('a.id = ?', $id)
                        ->join(array('u' => 'user'), 'a.user_id = u.id', 'login')
                        ->columns(array('user_id', 'title', 'text', 'image', 'views', 'date', 'date_edit', 'article_type_id', 'last_ip', 'content_type_id', 'publish'));
            default:

                break;
        }

        $result = $this->getDbTable()
                ->fetchRow($select);

        if (0 == count($result)) {
            return 'null';
        }

        switch ($action) {
            case 'view':
                //update counts of views
                if ($result->last_ip != $_SERVER['REMOTE_ADDR']) {
                    $entry = new Application_Model_Article();
                    $data = array(
                        'views' => ($result->views + 1),
                        'last_ip' => $_SERVER['REMOTE_ADDR']
                    );
                    $this->getDbTable()->update($data, array('id = ?' => $result->id));
                    $result->views = $data['views'];
                }
                break;
            default:

                break;
        }

        return $result;
    }

    public function getLastArticlesData($count, $order) {
        $select = $this->getDbTable()
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => 'article'), 'id')
                ->where('a.publish = 1')
                ->join(array('u' => 'user'), 'a.user_id = u.id', 'login')
                ->columns(array('user_id', 'title', 'text', 'image', 'views', 'date', 'date_edit', 'article_type_id', 'last_ip', 'content_type_id', 'publish'))
                ->order('id ' . $order);

        $result = $this->getDbTable()
                ->fetchAll($select);

        return $result;
    }

}