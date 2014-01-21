<?php

class Acl extends Zend_Acl {

	protected $roles;
	protected $resources;
	protected $rights;

	public function __construct() {
		// Init roles array from DB
		$this->getRolesArray();
		$this->initRoles();

		$this->getResourcesArray();
		$this->initResources();

		// Init rights array from db
		$this->initRights();

		$this->deny();

		$this->initPrivileges();
	}

	protected function getRolesArray() {
		$role_db = new Application_Model_DbTable_Role();
		$role_all = $role_db->getAll(FALSE, array("id", "name", "parent_role_id"), array('parent_role_id' => 'ASC'));

		if ($role_all) {
			$this->roles = array();
			foreach ($role_all as $role) {
				$this->roles[$role->id] = array(
					'id' => $role->id,
					'name' => $role->name,
					'parent_role_id' => $role->parent_role_id,
					'added' => 0,
				);
			}
		}
	}

	protected function getResourcesArray() {
		$resource_db = new Application_Model_DbTable_Resource();
		$resource_all = $resource_db->getAll(FALSE, array("id", "name", "parent_resource_id"), array('parent_resource_id' => 'ASC'));

		if ($resource_all) {
			$this->resources = array();
			foreach ($resource_all as $resource) {
				$this->resources[$resource->id] = array(
					'id' => $resource->id,
					'name' => $resource->name,
					'parent_resource_id' => $resource->parent_resource_id,
					'added' => 0,
				);
			}
		}
	}

	protected function initRoles() {
		if ($this->roles) {
			foreach ($this->roles as $role) {
				if (!$this->roles[$role['id']]['added']) {
					$this->addRecursiveRole($role['id']);
				}
			}
		}
	}

	protected function addRecursiveRole($role_id) {
		$parent_role_id = $this->roles[$role_id]['parent_role_id'];

		if ($parent_role_id) {
			if ($this->roles[$parent_role_id]['added']) {
				$this->addRole(new Zend_Acl_Role($this->roles[$role_id]['name']), $this->roles[$parent_role_id]['name']);
				$this->roles[$role_id]['added'] = 1;
			} else {
				$this->addRecursiveRole($parent_role_id);
				$this->addRecursiveRole($role_id);
			}
		} else {
			$this->addRole(new Zend_Acl_Role($this->roles[$role_id]['name']));
			$this->roles[$role_id]['added'] = 1;
		}
	}

	protected function initResources() {
		if ($this->resources) {
			foreach ($this->resources as $resource) {
				if (!$this->resources[$resource['id']]['added']) {
					$this->addRecursiveResource($resource['id']);
				}
			}
		}
	}

	protected function addRecursiveResource($resource_id) {
		$this->add(new Zend_Acl_Resource($this->resources[$resource_id]['name']));
		$this->resources[$resource_id]['added'] = 1;
	}

	protected function initRights() {
		$right_db = new Application_Model_DbTable_Right();
		$right_all = $right_db->getAll(FALSE, array("id", "name"));

		if ($right_all) {
			$this->rights = array();

			foreach ($right_all as $right) {
				$this->rights[$right->id] = array(
					'id' => $right->id,
					'name' => $right->name,
				);
			}
		}
	}

	protected function initPrivileges() {
		$privilege_db = new Application_Model_DbTable_Privilege();
		$privilege_all = $privilege_db->getAll(FALSE, "all");

		if ($privilege_all) {
			foreach ($privilege_all as $privilege) {
				$role = $this->roles[$privilege->role_id]['name'];
				$resource = $this->resources[$privilege->resource_id]['name'];
				$right = $this->rights[$privilege->right_id]['name'];

				$this->allow($role, $resource, $right);
			}
		}
	}

	public function can($privilege = 'show') {
		//Инициируем ресурс
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$resource = "{$request->getModuleName()}/{$request->getControllerName()}/{$request->getActionName()}";
		//Если ресурс не найден закрываем доступ
		if (!$this->has($resource))
			return true;

		//Inicialize role
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$storage_data = Zend_Auth::getInstance()->getStorage()->read();

			$user_role_db = new Application_Model_DbTable_UserRole();
			$role = $user_role_db->getItem(array('user_id' => $storage_data->id))->role_name;
		} else {
			$role = 'guest';
		}
		return $this->isAllowed($role, $resource, $privilege);
	}

	public function checkUserAccess($resource) {
		if (!$this->has($resource))
			return true;

		$privilege = 'show';

		//Inicialize role
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$storage_data = Zend_Auth::getInstance()->getStorage()->read();

			$user_role_db = new Application_Model_DbTable_UserRole();
			$role = $user_role_db->getItem(array('user_id' => $storage_data->id))->role_name;
		} else {
			$role = 'guest';
		}

		return $this->isAllowed($role, $resource, $privilege);
	}

	// function return user role
	public function getUser() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$storage_data = Zend_Auth::getInstance()->getStorage()->read();
			// get role name for current user
			$user = new Application_Model_DbTable_User();
			$role = $user->getUserRoleName($storage_data->id);
		} else {
			$role = 'guest';
		}

		return $role;
	}

}
