<?php

class Application_Model_DbTable_User extends Zend_Db_Table_Abstract {

    protected $_name = 'user';
    protected $_primary = 'id';

    public function getUserData($id) {
        $model = new self;
        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('u' => $this->_name), 'u.id')
                ->where('u.id = ? and u.enable = 1', $id)
                ->join(array('c' => 'country'), 'u.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
                    'country_url_image_glossy_wave' => 'c.url_image_glossy_wave',
                    'country_name' => 'c.name'))
                ->columns(array('login', 'email', 'name', 'surname', 'avatar_type', 'birthday', 'city', 'date_last_activity', 'date_create', 'skype',
            'icq', 'gtalk', 'www',));

        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            return $user;
        } else {
            return FALSE;
        }
    }

    public function getLogin($id) {
        $model = new self;
        $select = $model->select()
                ->from(array('u' => $this->_name), 'id')
                ->where('u.id = ?', $id)
                ->columns(array('login'));
        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            return $user->login;
        } else {
            return FALSE;
        }
    }

    public function getEmail($id) {
        $model = new self;
        $select = $model->select()
                ->from(array('u' => $this->_name), 'id')
                ->where('u.id = ?', $id)
                ->columns(array('email'));

        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            return $user->email;
        } else {
            return FALSE;
        }
    }

    public function getSimpleEnableUsersPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('u' => 'user'), 'u.id')
                                ->where('u.user_role_id != 1 and u.enable = 1')
                                ->join(array('c' => 'country'), 'u.country_id = c.id', array(
                                    'country_url_image_round' => 'c.url_image_round',
                                    'country_name' => 'c.name')
                                )
                                ->columns(array('id', 'avatar_type', 'login'))
                                ->order('id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function getAllUsersPager($count, $page, $page_range, $order) {
        $model = new self;

        $adapter = new Zend_Paginator_Adapter_DbSelect($model
                                ->select()
                                ->setIntegrityCheck(false)
                                ->from(array('u' => 'user'), 'u.id')
                                ->where('u.user_role_id != 1 and u.enable = 1')
                                ->join(array('c' => 'country'), 'u.country_id = c.id', array(
                                    'country_url_image_round' => 'c.url_image_round',
                                    'country_name' => 'c.name')
                                )
                                ->columns('*')
                                ->order('id ' . $order)
        );

        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($count);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange($page_range);

        return $paginator;
    }

    public function setLastActivity($user_id) {
        $model = new self;

        $user_data = array(
            'date_last_activity' => date('Y-m-d H:i:s')
        );

        $user_where = $model->getAdapter()->quoteInto('id = ?', $user_id);
        $model->update($user_data, $user_where);
    }

    public function activateUser($email, $password, $code_activate) {
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

    public function setRestorePassCode($email, $code_restore) {
        $model = new self;

        $user_data = array(
            'code_restore_pass' => $code_restore
        );

        $user_where = $model->getAdapter()->quoteInto('email = ?', $email);
        $model->update($user_data, $user_where);
    }

    public function restoreNewPasswd($email, $code_restore, $password) {
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

    public function checkUserStatus($email) {
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

    public function getUserAvatarLoad($id) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('id = ?', $id)
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

    public function getUserAvatarLink($user_id) {
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

    public function getUserAvatarGravatarEmail($user_id) {
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

    public function getUserStatus($id) {
        $model = new self;

        $select = $model
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('u' => $this->_name), 'u.id')
                ->where('u.id = ?', $id)
                ->join(array('u_r' => 'user_role'), 'u_r.id = u.user_role_id', array('user_role' => 'u_r.name'))
                ->columns(array('code_activate', 'enable'));

        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            if ($user->enable == '1') {
                return $user->user_role;
            } else {
                return 'DISABLE';
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
            return FALSE;
        }
    }

    public function getUsersByRoleId($role_id, $order) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
                ->where('user_role_id = ?', $role_id)
                ->columns(array('id', 'login', 'name', 'surname'))
                ->order('surname ' . $order);

        $user_data = $model->fetchAll($select);

        if (count($user_data) != 0) {
            return $user_data;
        } else {
            return FALSE;
        }
    }

    public function getUsersByRoleName($role_name, $order) {
        $model = new self;
        $user_role = new Application_Model_DbTable_UserRole();
        $user_role_id = $user_role->getId($role_name);

        $select = $model->select()
                ->from('user', 'id')
                ->where('user_role_id = ?', $user_role_id)
                ->columns(array('id', 'login', 'name', 'surname'))
                ->order('surname ' . $order);

        $users = $model->fetchAll($select);

        if (count($users) != 0) {
            return $users;
        } else {
            return FALSE;
        }
    }

    public function setNewUserPassword($id, $oldPassword, $newPassword) {
        $model = new self;

        $select = $model
                ->select()
                ->from($this->_name, 'id')
                ->where('id = ?', $id)
                ->where('password = ?', sha1($oldPassword));
        $user = $model->fetchRow($select);

        if (count($user) != 0) {
            $user_where = $model->getAdapter()->quoteInto('id = ?', $id);
            $user_data = array(
                'password' => sha1($newPassword)
            );

            $model->update($user_data, $user_where);
            return TRUE;
        } else {
            return FALSE;
        }
    }

}