<?php

class Application_Model_DbTable_Event extends Zend_Db_Table_Abstract {

    protected $_name = 'event';
    protected $_primary = 'id';

    public function getData($id) {
        $model = new self;

        $select = $model
                ->select()
                ->from(array('e' => $this->_name), 'id')
                ->where('e.id = ?', $id)
                ->columns('*');

        $event = $model->fetchRow($select);

        if (count($event) != 0) {
            return $event;
        } else {
            return FALSE;
        };
    }
    
    public function getNext(){
        $model = new self();
        
        $date = new Zend_Date();
        $date = $date->toString('yyyy-MM-dd HH:mm:ss');
        
        $select = $model
                ->select()
                ->from(array('e' => $this->_name), 'id')
                ->where('e.date_event >= ?', $date)
                ->columns('*')
                ->order('date_event ASC');

        $event = $model->fetchRow($select);

        if (count($event) != 0) {
            return $event;
        } else {
            return FALSE;
        };
    }
    
    public function getEventsPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->from($this->_name, 'id')
                                ->columns('*')
                                ->order('date_event ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

}