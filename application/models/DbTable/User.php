<?php

class Application_Model_DbTable_User extends Zend_Db_Table_Abstract {

    protected $_name = 'user';
    protected $_primary = 'id';
    protected $db_href = 'pri';

    public function getUserData($id) {
        $model = new self;
        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('u' => $this->_name), 'u.id')
                ->where('u.id = ? and u.enable = 1', $id)
                ->join(array('c' => 'country'), 'u.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
                    'country_url_image_glossy_wave' => 'c.url_image_glossy_wave',
                    'country_native_name' => 'c.native_name',
                    'country_english_name' => 'c.english_name',))
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
                        //->where('u.user_role_id != 1 and u.enable = 1')
                        ->join(array('c' => 'country'), 'u.country_id = c.id', array(
                            'country_url_image_round' => 'c.url_image_round',
                            'country_name' => 'c.native_name')
                        )
                        ->columns(array('id', 'avatar_type', 'login', 'date_last_activity'))
                        ->order('date_last_activity ' . $order)
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
                        //->where('u.user_role_id != 1 and u.enable = 1')
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

    public function setLastLoginIP($user_id, $ip) {
        $model = new self;

        $user_data = array(
            'last_login_ip' => $ip
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

                $role_db = new Application_Model_DbTable_Role();
                $role_data = $role_db->getItem(array("name" => array("value" => "user")), array("id", "name"));
                $user_role_db = new Application_Model_DbTable_UserRole();

                $new_user_role_data = array(
                    'user_id' => $user->id,
                    'role_id' => $role_data->id,
                );

                $user_where = $user_role_db->getAdapter()->quoteInto('user_id = ?', $user->id);
                $updated = $user_role_db->update($new_user_role_data, $user_where);

                if (!$updated) {
                    $new_user_role = $user_role_db->createRow($new_user_role_data);
                    $new_user_role->save();
                }

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
                //->join(array('u_r' => 'user_role'), 'u_r.id = u.user_role_id', array('user_role' => 'u_r.name'))
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

    public function getAllUsers($order) {
        $model = new self;

        $select = $model->select()
                ->from('user', 'id')
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

    public function getCountOnlineUsers() {
        $model = new self;

        $date = new Zend_Date();
        $date->sub(7, Zend_Date::MINUTE);
        $date = $date->toString('yyyy-MM-dd HH:mm:ss');

        return $model->fetchAll(array('date_last_activity >= ?' => $date))->count();
    }

    /*
     * Get Item by idencity field value and $field array of fields list.
     */

    public function getItem($idencity = array(), $fields = array()) {
        $model = new self;

        if (!count($idencity)) {
            return FALSE;
        } elseif (is_array($idencity)) {
            $idencity_field = $idencity[0];
            $idencity_value = $idencity[1];
        } elseif (is_int($idencity)) {
            $idencity_field = 'id';
            $idencity_value = $idencity;
        }

        if (!isset($idencity_field) || !isset($idencity_value)) {
            return FALSE;
        }

        if (is_array($fields)) {
            if (count($fields)) {
                $fields = array_map('trim', $fields);
            } else {
                $fields = "*";
            }
        } elseif (is_string($fields)) {
            if (strtolower($fields) == "all") {
                $fields = "*";
            } else {
                $fields = array_map('trim', explode(",", $fields));
            }
        }

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array('u' => $this->_name))
                ->where('u.' . $idencity_field . ' = ' . $idencity_value)
                ->join(array('c' => 'country'), 'u.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
                    'country_url_image_glossy_wave' => 'c.url_image_glossy_wave',
                    'country_native_name' => 'c.native_name',
                    'country_english_name' => 'c.english_name',))
                ->joinLeft(array('ur' => 'user_role'), 'u.id = ur.user_id', array('user_role_id' => 'ur.role_id'))
                ->joinLeft(array('rl' => 'role'), 'ur.role_id = rl.id', array('user_role_name' => 'rl.name'))
                ->columns($fields);

        $resource = $model->fetchRow($select);

        if (count($resource) != 0) {
            return $resource;
        } else {
            return FALSE;
        }
    }

    /*
     * Function returns array of Items with $fields array of fields list.
     * Sorted by $order value
     * 
     * If $pager == TRUE function return Pager with $pager_args parameters
     * 
     * Parameters:
     * $pager_args['page_count_items']	- Count items for page
     * $pager_args['page']		- Number of curent page
     * $pager_args['page_range']	- Range of pages displaying at the pager's block
     * 
     */

    public function getAll($idencity = array(), $fields = array(), $order = array(), $pager = FALSE, array $pager_args = array()) {
        $model = new self;
        $idencity_data = "";
        $order_data = "";

        // idencity fields list
        if ($idencity) {
            if (is_array($idencity)) {
                foreach ($idencity as $field => $value) {
                    if ($idencity_data) {
                        if (isset($value['condition'])) {
                            if ($value['condition']) {
                                $condition = $value['condition'];
                            } else {
                                $condition = "OR";
                            }
                        } else {
                            $condition = "OR";
                        }

                        $idencity_data .= sprintf(" %s %s.%s = '%s'", $condition, $this->db_href, $field, $value['value']);
                    } else {
                        $idencity_data = sprintf("%s.%s = %s", $this->db_href, $field, $value['value']);
                    }
                }
            } elseif (is_int($idencity) || is_string($idencity)) {
                $idencity_data = sprintf("%s.id = %s", $this->db_href, $idencity);
            }
        }

        // fields list
        if ($fields) {
            if (is_array($fields)) {
                $fields = array_map('trim', $fields);
            } elseif (is_string($fields)) {
                if (strtolower($fields) == "all") {
                    $fields = "*";
                } else {
                    $fields = array_map('trim', explode(",", $fields));
                }
            }
        }

        // order list
        if ($order) {
            if (is_array($order)) {
                foreach ($order as $field => $value) {
                    if ($order_data) {
                        $order_data .= sprintf(", %s.%s %s", $this->db_href, $field, $value);
                    } else {
                        $order_data = sprintf("%s.%s %s", $this->db_href, $field, $value);
                    }
                }
            }
        }

        $select = $model->select()
                ->setIntegrityCheck(false)
                ->from(array($this->db_href => $this->_name))
                ->join(array('c' => 'country'), $this->db_href . '.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
                    'country_url_image_glossy_wave' => 'c.url_image_glossy_wave',
                    'country_native_name' => 'c.native_name',
                    'country_english_name' => 'c.english_name',))
                ->joinLeft(array('ur' => 'user_role'), $this->db_href . '.id = ur.user_id', array('user_role_id' => 'ur.role_id'))
                ->joinLeft(array('rl' => 'role'), 'ur.role_id = rl.id', array('user_role_name' => 'rl.name'));

        if ($fields) {
            $select->columns($fields);
        } else {
            $select->columns("*");
        }

        if ($idencity_data) {
            $select->where($idencity_data);
        }

        if ($order_data) {
            $select->order($order_data);
        }

        if ($pager) {
            $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);

            $paginator = new Zend_Paginator($adapter);
            if (count($pager_args)) {
                $paginator->setItemCountPerPage($pager_args['page_count_items']);
                $paginator->setCurrentPageNumber($pager_args['page']);
                $paginator->setPageRange($pager_args['page_range']);
            } else {
                $paginator->setItemCountPerPage("10");
                $paginator->setCurrentPageNumber("1");
                $paginator->setPageRange("5");
            }

            return $paginator;
        } else {
            $resources = $model->fetchAll($select);

            if (count($resources) != 0) {
                return $resources;
            } else {
                return FALSE;
            }
        }
    }

}
