<?php

class Application_Model_DbTable_Article extends Zend_Db_Table_Abstract {

    protected $_name = 'article';
    protected $_primary = 'id';

    public function get_published_article_data($article_id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => 'article'), 'a.id')
                ->where('a.id = ? and a.publish = 1', $article_id)
                ->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
                ->columns(array('a.user_id', 'a.title', 'a.text', 'a.image', 'a.views', 'a.date_create',
            'a.date_edit', 'a.article_type_id', 'a.last_ip', 'a.content_type_id', 'a.publish'));

        $article = $model->fetchRow($select);
        return $article;
    }

    public function get_article_data($article_id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('a' => 'article'), 'a.id')
                ->where('a.id = ' . $article_id)
                ->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
                ->columns(array('a.user_id', 'a.title', 'a.text', 'a.image', 'a.views', 'a.date_create',
            'a.date_edit', 'a.article_type_id', 'a.last_ip', 'a.content_type_id', 'a.publish'));

        $article = $model->fetchRow($select);
        return $article;
    }

    public function get_publish_article_pager($count, $page, $page_range, $article_type, $order) {
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
    
    public function get_last_publish_article($count, $order){
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