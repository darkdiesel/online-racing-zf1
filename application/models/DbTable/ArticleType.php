<?php

class Application_Model_DbTable_ArticleType extends Zend_Db_Table_Abstract {

    protected $_name = 'article_type';
    protected $_primary = 'id';

    public function get_name($article_type_id) {
        $model = new self;
        $select = $model->select()
                ->from(array('a_t' => $this->_name), 'id')
                ->where('a_t.id = ?', $article_type_id)
                ->columns(array('name'));
        $article_type = $model->fetchRow($select);
        return $article_type->name;
    }
    
    public function get_id($article_type_name) {
        $model = new self;
        $select = $model->select()
                ->from(array('a_t' => $this->_name), 'name')
                ->where('a_t.name = ?', $article_type_name)
                ->columns(array('id'));
        $article_type = $model->fetchRow($select);
        return $article_type->id;
    }
    
    public function get_article_type_pager($count, $page, $page_range, $order) {
        $model = new self;
        
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                        ->select()
                                        ->from('article_type', 'id')
                                        ->columns(array('id', 'name', 'description','date_create', 'date_edit'))
                                        ->order('id ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

}