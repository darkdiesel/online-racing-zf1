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
    
    public function set_last_activite($user_id){
        
    }

}