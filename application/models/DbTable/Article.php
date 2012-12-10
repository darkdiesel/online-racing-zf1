<?php

class Application_Model_DbTable_Article extends Zend_Db_Table_Abstract {

    protected $_name = 'article';
    protected $_primary = 'id';

    public function delete_article($article_id) {
        $model = new self;
        $row = $model->fetchRow($model->select()->where('id = ?', $article_id));
        $row->delete();
    }
    
    
    

    /*public function delete($article_id) {
        $model = new self;
        $row = $model->fetchRow($model->select()->where('id = ?', $article_id));
        //$row->delete();

        //$model->delete($model->select()->where('id = ?', $article_id));
    }*/

}