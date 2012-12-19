<?php

class Application_Model_DbTable_Article extends Zend_Db_Table_Abstract {

    protected $_name = 'article';
    protected $_primary = 'id';

    public function getPublishedArticleData($article_id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => 'article'), 'a.id')
                ->where('a.id = ? and a.publish = 1', $article_id)
                ->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
                ->columns(array('a.id', 'a.user_id', 'a.title', 'a.text', 'a.image', 'a.views', 'a.date_create',
            'a.date_edit', 'a.article_type_id', 'a.last_ip', 'a.content_type_id', 'a.publish'));

        $article = $model->fetchRow($select);

        if (count($article) != 0) {
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

    public function getArticleData($article_id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => 'article'), 'a.id')
                ->where('a.id = ' . $article_id)
                ->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
                ->columns(array('a.user_id', 'a.title', 'a.text', 'a.image', 'a.views', 'a.date_create',
            'a.date_edit', 'a.article_type_id', 'a.last_ip', 'a.content_type_id', 'a.publish'));

        $article = $model->fetchRow($select);

        if (count($article) != 0) {
            if ($article->last_ip != $_SERVER['REMOTE_ADDR']) {

                $article_data = array(
                    'views' => ($article->views = $article->views + 1),
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

    public function getPublishArticlePagerByType($count, $page, $page_range, $article_type, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('a' => 'article'), 'id')
                                ->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
                                ->columns(array('a.id', 'a.user_id', 'a.title', 'a.text', 'a.image', 'a.views', 'a.date_create', 'a.date_edit'))
                                ->where('publish=1 and article_type_id=' . $article_type)
                                ->order('a.id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function getPublishArticleTitlesByType($article_type, $order) {
        $model = new self;

        $select = $model->select()
                ->from('article', 'id')
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
                ->from('article', 'id')
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

    public function getAllPublishArticlePager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('a' => 'article'), 'id')
                                ->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
                                ->columns(array('a.id', 'a.user_id', 'a.title', 'a.text', 'a.image', 'a.views', 'a.date_create', 'a.date_edit'))
                                ->where('publish=1')
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
                ->from('article', 'id')
                ->where('publish = 1')
                ->columns(array('title', 'text', 'image', 'content_type_id'))
                ->order('id ' . $order);

        $result = $model->fetchAll($select);

        return $result;
    }

}