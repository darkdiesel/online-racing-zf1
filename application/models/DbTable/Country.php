<?php

class Application_Model_DbTable_Country extends Zend_Db_Table_Abstract {

    protected $_name = 'country';
    protected $_primary = 'id';
    
    public function get_name($content_type_id) {
        $model = new self;
        $select = $model->select()
                ->from(array('с_t' => $this->_name), 'id')
                ->where('с_t.id = ?', $content_type_id)
                ->columns(array('name'));
        $content_type = $model->fetchRow($select);
        return $content_type->name;
    }
    
    public function get_id($content_type_name) {
        $model = new self;
        $select = $model->select()
                ->from(array('с_t' => $this->_name), 'name')
                ->where('с_t.name = ?', $content_type_name)
                ->columns(array('id'));
        $content_type = $model->fetchRow($select);
        return $content_type->id;
    }
    
    public function get_content_type_pager($count, $page, $page_range, $order) {
        $model = new self;
        
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                        ->select()
                                        ->from('content_type', 'id')
                                        ->columns(array('id', 'name', 'description','date_create', 'date_edit'))
                                        ->order('id ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

}