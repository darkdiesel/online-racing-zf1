<?php

class Application_Model_DbTable_User extends Zend_Db_Table_Abstract {

    protected $_name = 'user';
    protected $_primary = 'id';

    public function get_login($user_id) {
        $model = new self;
        $select = $model->select()
                ->from(array('u' => $this->_name), 'id')
                ->where('u.id = ?', $user_id)
                ->columns(array('login'));
        $user = $model->fetchRow($select);
        return $user->login;
    }

    public function get_users_pager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbSelect($model
                                ->select()
                                ->from(array('u' => 'user'), 'id')
                                ->where('u.user_role_id != 1')
                                ->columns(array('gravatar', 'login'))
                                ->order('u.id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function get_all_users_pager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbSelect($model
                                ->select()
                                ->from(array('u' => 'user'), 'id')
                                ->columns(array('gravatar', 'login'))
                                ->order('u.id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function set_last_activity($user_id) {
        $model = new self;

        $user_data = array(
            'date_last_active' => date('Y-m-d H:i:s')
        );

        $user_where = $model->getAdapter()->quoteInto('id = ?', $user_id);
        $model->update($user_data, $user_where);
    }

    public function activate_user($email, $password, $code_activate) {
        $model = new self;

        $user = $model->fetchRow(array('email = ?' => $email, 'password = ?' => $password, 'code_activate = ?' => $code_activate));

        if (count($user) != 0) {
            $user_data = array(
                'code_activate' => ''
            );

            $user_where = $model->getAdapter()->quoteInto('id = ?', $user->id);
            $model->update($user_data, $user_where);
            return true;
        } else {
            return false;
        }
    }

    public function set_restore_pass_code($email, $code_restore) {
        $model = new self;

        $user_data = array(
            'code_restore_pass' => $code_restore
        );

        $user_where = $model->getAdapter()->quoteInto('email = ?', $email);
        $model->update($user_data, $user_where);
    }

    public function restore_passwd($email, $code_restore, $password){
        $model = new self;

        $user = $model->fetchRow(array('email = ?' => $email, 'code_restore_pass = ?' => $code_restore));

        if (count($user) != 0) {
            $user_data = array(
                'password' => $password,
                'code_restore_pass' => ''
            );

            $user_where = $model->getAdapter()->quoteInto('id = ?', $user->id);
            $model->update($user_data, $user_where);
            return true;
        } else {
            return false;
        }
    }

}