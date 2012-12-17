<?php

class Application_Model_DbTable_UserRole extends Zend_Db_Table_Abstract {

    protected $_name = 'user_role';
    protected $_primary = 'id';

    public function get_id($role_name) {
        $model = new self;

        $select = $model->select()
                ->from($this->_name, 'id')
                ->where('name = ?', $role_name)
                ->columns('id');

        $user_role_data = $model->fetchRow($select);

        if (count($user_role_data) != 0) {
            return $user_role_data->id;
        } else {
            return FALSE;
        }
    }

}