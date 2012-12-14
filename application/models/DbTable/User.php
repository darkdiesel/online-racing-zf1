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
                                ->from('user', 'id')
                                ->where('user_role_id != 1 and enable = 1')
                                ->columns(array('id', 'avatar_type', 'login'))
                                ->order('id ' . $order)
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
            'date_last_activity' => date('Y-m-d H:i:s')
        );

        $user_where = $model->getAdapter()->quoteInto('id = ?', $user_id);
        $model->update($user_data, $user_where);
    }

    public function activate_user($email, $password, $code_activate) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('email = ?', $email)
                ->where('password = ?', $password)
                ->columns(array('code_activate'));

        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            if ($user->code_activate == $code_activate) {
                $user_data = array(
                    'code_activate' => ''
                );

                $user_where = $model->getAdapter()->quoteInto('id = ?', $user->id);
                $model->update($user_data, $user_where);

                return 'done';
            } elseif ($user->code_activate == '') {
                return 'activate';
            } else {
                return 'error';
            }
        } else {
            return 'notFound';
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

    public function restore_new_passwd($email, $code_restore, $password) {
        $model = new self;

        $user = $model->fetchRow(array('email = ?' => $email, 'code_restore_pass = ?' => $code_restore));

        if (count($user) != 0) {
            $user_data = array(
                'password' => $password,
                'code_restore_pass' => ''
            );

            $user_where = $model->getAdapter()->quoteInto('id = ?', $user->id);
            $model->update($user_data, $user_where);
            return TRUE;
        } else {
            $user = $model->fetchRow(array('email = ?' => $email));
            return FALSE;
        }
    }

    public function check_user_status($email) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('email = ?', $email)
                ->columns(array('enable', 'code_activate'));

        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            if ($user->code_activate == '') {
                if ($user->enable == 1) {
                    return 'enable';
                } else {
                    return 'disable';
                }
            } else {
                return 'notActivate';
            }
        } else {
            return 'notFound';
        }
    }

    public function get_user_avatar_load($user_id) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('id = ?', $user_id)
                ->columns(array('avatar_load'));

        $avatar = $model->fetchRow($select);

        if (count($avatar) != 0) {
            if ($avatar->avatar_load != '') {
                return $avatar->avatar_load;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    public function get_user_avatar_link($user_id) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('id = ?', $user_id)
                ->columns(array('avatar_link'));

        $avatar = $model->fetchRow($select);

        if (count($avatar) != 0) {
            if ($avatar->avatar_link != '') {
                return $avatar->avatar_link;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    public function get_user_avatar_gravatar_email($user_id) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('id = ?', $user_id)
                ->columns(array('avatar_gravatar_email'));

        $avatar = $model->fetchRow($select);

        if (count($avatar) != 0) {
            if ($avatar->avatar_gravatar_email != '') {
                return $avatar->avatar_gravatar_email;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    public function getUserRoleName($user_id) {
        $model = new self;

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('u' => 'user'), 'u.id')
                ->where('u.id = ?', $user_id)
                ->join(array('u_r' => 'user_role'), 'u_r.id = u.user_role_id', array('user_role' => 'u_r.name'))
                ->columns();

        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            return $user->user_role;
        } else {
            
        }
    }

}