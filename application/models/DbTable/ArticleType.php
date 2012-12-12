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

}