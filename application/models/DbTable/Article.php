<?php

class Application_Model_DbTable_Article extends Zend_Db_Table_Abstract {

    protected $_name = 'article';
    protected $_primary = 'id';

    public function getPublishedArticleData($id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => $this->_name), 'a.id')
                ->where('a.id = ?', $id)
                ->where('a.publish = 1')
                ->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
                ->join(array('a_t' => 'article_type'), 'a_t.id = a.article_type_id', array('article_type_name' => 'a_t.name'))
                ->columns('*');

        $article = $model->fetchRow($select);

        if (count($article) != 0) {
            // update count of views
            if ($article->last_ip != $_SERVER['REMOTE_ADDR']) {
                $article_data = array(
                    'views' => ($article->views + 1),
                    'last_ip' => $_SERVER['REMOTE_ADDR']
                );

                $article_where = $model->getAdapter()->quoteInto('id = ?', $article_id);
                $model->update($article_data, $article_where);
            }

            return $article;
        } else {
            return FALSE;
        }
    }

    public function getArticleData($id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => $this->_name), 'a.id')
                ->where('a.id = ' . $id)
                ->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
                ->join(array('a_t' => 'article_type'), 'a_t.id = a.article_type_id', array('article_type_name' => 'a_t.name'))
                ->columns('*');

        $article = $model->fetchRow($select);

        if (count($article) != 0) {
            // update count of views
            if ($article->last_ip != $_SERVER['REMOTE_ADDR']) {

                $article_data = array(
                    'views' => ($article->views = $article->views + 1),
                    'last_ip' => $_SERVER['REMOTE_ADDR']
                );

                $article_where = $model->getAdapter()->quoteInto('id = ?', $id);
                $model->update($article_data, $article_where);
            }

            return $article;
        } else {
            return FALSE;
        }
    }

    public function getPublishArticleTitlesByType($article_type, $order) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'id')
                ->where('publish=1 and article_type_id=' . $article_type)
                ->order('title ' . $order)
                ->columns(array('id', 'title'));

        $articles = $model->fetchAll($select);

        if (count($articles) != 0) {
            return $articles;
        } else {
            return FALSE;
        }
    }

    public function getAllArticleTitlesByType($article_type, $order) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'id')
                ->where('article_type_id=' . $article_type)
                ->order('title ' . $order)
                ->columns(array('id', 'title'));

        $articles = $model->fetchAll($select);

        if (count($articles) != 0) {
            return $articles;
        } else {
            return FALSE;
        }
    }

    public function getPublishedArticlesPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('a' => $this->_name), 'id')
                                ->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
                                ->join(array('a_t' => 'article_type'), 'a_t.id = a.article_type_id', array('article_type_name' => 'a_t.name'))
                                ->columns(array('a.id', 'a.user_id', 'a.title', 'a.annotation', 'a.text', 'a.image', 'a.views', 'a.date_create', 'a.date_edit'))
                                ->where('publish = 1')
                                ->order('a.id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function getAllArticlesPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('a' => $this->_name), 'id')
                                ->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
                                ->join(array('a_t' => 'article_type'), 'a_t.id = a.article_type_id', array('article_type_name' => 'a_t.name'))
                                ->columns(array('a.id', 'a.article_type_id', 'a.user_id', 'a.title', 'a.annotation', 'a.text', 'a.image', 'a.views', 'a.date_create', 'a.date_edit'))
                                ->order('a.id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function getAllArticlesPagerByType($count, $page, $page_range, $article_type, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('a' => $this->_name), 'id')
                                ->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
                                ->join(array('a_t' => 'article_type'), 'a_t.id = a.article_type_id', array('article_type_name' => 'a_t.name'))
                                ->columns(array('a.id', 'a.user_id', 'a.title', 'a.annotation', 'a.text', 'a.image', 'a.views', 'a.date_create', 'a.date_edit'))
                                ->where('article_type_id=' . $article_type)
                                ->where('publish = 1')
                                ->order('a.id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function getLastPublishArticle($count, $order) {
        $model = new self;

        $select = $model
                ->select()
                ->from($this->_name, 'id')
                ->where('publish = 1')
                ->columns(array('title', 'annotation', 'text', 'image', 'content_type_id'))
                ->limit($count, 0)
                ->order('id ' . $order);

        $result = $model->fetchAll($select);

        return $result;
    }

    public function getPublishArticleTitlesByTypeName($article_type_name, $order) {
        $model = new self;

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_type_id = $article_type->getId($article_type_name);

        if (count($article_type_id) != 0) {
            $select = $model->select()
                    ->from($this->_name, 'id')
                    ->where('publish = 1 and article_type_id = ' . $article_type_id)
                    ->order('title ' . $order)
                    ->columns(array('id', 'title'));

            $articles = $model->fetchAll($select);

            if (count($articles) != 0) {
                return $articles;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

}