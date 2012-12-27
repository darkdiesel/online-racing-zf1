<?php

class Application_Model_DbTable_League extends Zend_Db_Table_Abstract {

    protected $_name = 'league';
    protected $_primary = 'id';

    public function getLeaguePager($count, $page, $page_range, $order) {
        $model = new self;
        
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->from('league', 'id')
                                ->columns(array('id', 'name', 'logo', 'description', 'date_create', 'date_edit'))
                                ->order('id ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }
    
    public function getLeaguesName($order) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'name')
                ->columns(array('id', 'name'))
                ->order('name ' . $order);

        $leagues = $model->fetchAll($select);

        if (count($leagues) != 0) {
            return $leagues;
        } else {
            return FALSE;
        }
    }

}