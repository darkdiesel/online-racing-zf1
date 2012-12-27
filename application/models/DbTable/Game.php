<?php

class Application_Model_DbTable_Game extends Zend_Db_Table_Abstract {

    protected $_name = 'game';

    public function getGameNames($order) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'name')
                ->columns(array('id', 'name'))
                ->order('name ' . $order);

        $games = $model->fetchAll($select);

        if (count($games) != 0) {
            return $games;
        } else {
            return FALSE;
        }
    }

}