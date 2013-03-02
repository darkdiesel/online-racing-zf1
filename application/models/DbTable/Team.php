<?php

class Application_Model_DbTable_Team extends Zend_Db_Table_Abstract {

    protected $_name = 'team';
    protected $_primary = 'id';

    public function getName($team_id) {
        $model = new self;
        $select = $model->select()
                ->from(array('t' => $this->_name), 'id')
                ->where('t.id = ?', $team_id)
                ->columns(array('name'));
        $team = $model->fetchRow($select);

        if (count($team) != 0) {
            return $team->name;
        } else {
            return FALSE;
        }
    }

    public function getId($team_name) {
        $model = new self;
        $select = $model->select()
                ->from(array('t' => $this->_name), 'name')
                ->where('t.name = ?', $team_name)
                ->columns(array('id'));
        $team = $model->fetchRow($select);

        if (count($team) != 0) {
            return $team->id;
        } else {
            return FALSE;
        }
    }

    public function getTeamsPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($model
                                ->select()
                                ->from('team', 'id')
                                ->columns(array('id', 'name', 'description', 'date_create', 'date_edit'))
                                ->order('id ' . $order));

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }
    
    public function getTeamNames($order) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'name')
                ->columns(array('id', 'name'))
                ->order('name ' . $order);

        $teams = $model->fetchAll($select);

        if (count($teams) != 0) {
            return $teams;
        } else {
            return FALSE;
        }
    }

}