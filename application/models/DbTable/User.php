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
					'country_ImageGlossyWaveUrl' => 'c.ImageGlossyWaveUrl',
					'country_NativeName' => 'c.NativeName',
					'country_EnglishName' => 'c.EnglishName',))
				->columns(array('Login', 'Email', 'Name', 'Surname', 'AvatarType', 'DateBirthday', 'City', 'DateLastActivity', 'DateCreate', 'Skype',
			'Icq', 'Gtalk', 'WebSite',));

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

	public function setLastActivity($user_id) {
		$model = new self;

		$user_data = array(
			'DateLastActivity' => date('Y-m-d H:i:s')
		);

		$user_where = $model->getAdapter()->quoteInto('ID = ?', $user_id);
		$model->update($user_data, $user_where);
	}

	public function setLastLoginIP($user_id, $ip) {
		$model = new self;

		$user_data = array(
			'LastUserLoginIP' => $ip
		);

		$user_where = $model->getAdapter()->quoteInto('ID = ?', $user_id);
		$model->update($user_data, $user_where);
	}

	public function activateUser($email, $password, $code_activate) {
		$model = new self;

		$select = $model->select()
				->from('User', 'ID')
				->where('Email = ?', $email)
				->where('Password = ?', $password)
				->columns(array('ActivationCode'));

		$user = $model->fetchRow($select);

		if (count($user) != 0) {
			if ($user->ActivationCode == $code_activate) {
				$user_data = array(
					'ActivationCode' => ''
				);

				$user_where = $model->getAdapter()->quoteInto('id = ?', $user->id);
				$model->update($user_data, $user_where);

				$role_db = new Application_Model_DbTable_Role();
				$role_data = $role_db->getItem(array("Name" => array("value" => "user")), array("ID", "Name"));
				$user_role_db = new Application_Model_DbTable_UserRole();

				$new_user_role_data = array(
					'UserID' => $user->ID,
					'RoleID' => $role_data->id,
				);

				$user_where = $user_role_db->getAdapter()->quoteInto('UserID = ?', $user->ID);
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
		/*
		  // get role_id by role_name
		  $role_db = new Application_Model_DbTable_Role();
		  $role_data = $role_db->getItem(array('name' => $role_name));

		  //get users with role_id
		  $user_role_db = new Application_Model_DbTable_UserRole();
		  $user_role_data = $user_role_db->getItem(array('role_id' => $role_data->id)); */

		$select = $model->select()
				->from('user', 'id')
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

	/*
	 * Get Item by idencity field value and $field array of fields list.
	 */

	public function getItem($idencity = array(), $fields = array()) {
		$model = new self;
		$db = new App_Controller_Action_Helper_DB();
		$idencity_data = "";

		// idencity fields list
		if (count($idencity)) {
			$idencity_data = $db->getIdencity($idencity, $this->db_href);
		} else {
			return FALSE;
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

		$select = $model->select()
				->setIntegrityCheck(false)
				->from(array($this->db_href => $this->_name))
				->join(array('c' => 'country'), $this->db_href . '.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
					'country_ImageGlossyWaveUrl' => 'c.ImageGlossyWaveUrl',
					'country_NativeName' => 'c.NativeName',
					'country_EnglishName' => 'c.EnglishName',))
				->joinLeft(array('ur' => 'user_role'), $this->db_href . '.id = ur.user_id', array('user_role_id' => 'ur.role_id'))
				->joinLeft(array('rl' => 'role'), 'ur.role_id = rl.id', array('user_role_name' => 'rl.name'))
				->where($idencity_data);

		if ($fields) {
			$select->columns($fields);
		} else {
			$select->columns("*");
		}

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

	public function getAll($idencity = array(), $fields = array(), $order = "ASC", $pager = FALSE, array $pager_args = array()) {
		$model = new self;
		$idencity_data = "";
		$order_data = "";

		$db = new App_Controller_Action_Helper_DB();

		// idencity fields list
		if ($idencity) {
			$idencity_data = $db->getIdencity($idencity, $this->db_href);
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
			} elseif (is_string($order) && !empty($order)) {
				$order_data = sprintf("%s.id %s", $this->db_href, $order);
			}
		}

		$select = $model->select()
				->setIntegrityCheck(false)
				->from(array($this->db_href => $this->_name))
				->join(array('c' => 'country'), $this->db_href . '.country_id = c.id', array('country_abbreviation' => 'c.abbreviation',
					'country_ImageGlossyWaveUrl' => 'c.ImageGlossyWaveUrl',
					'country_ImageRoundUrl' => 'c.ImageRoundUrl',
					'country_NativeName' => 'c.NativeName',
					'country_EnglishName' => 'c.EnglishName',))
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

			if (count($paginator) > 0) {
				return $paginator;
			} else {
				return FALSE;
			}
		} else {
			$resources = $model->fetchAll($select);

			if (count($resources) > 0) {
				return $resources;
			} else {
				return FALSE;
			}
		}
	}

}
