<?php
class Acl extends Zend_Acl {
	public function  __construct() {
		//Добавляем роли
		$this->addRole('guest');
		$this->addRole('user', 'guest');
		$this->addRole('admin', 'user');
		$this->addRole('master', 'admin');
		
                // resources
		$this->addResource(new Zend_Acl_Resource('user/register'));
                $this->addResource(new Zend_Acl_Resource('user/activate'));
                $this->addResource(new Zend_Acl_Resource('user/restorepasswd'));
                
		//Add resources
		// guest resources
		$this->add(new Zend_Acl_Resource('guest_allow'));
		$this->add(new Zend_Acl_Resource('index/index'),'guest_allow');
		$this->add(new Zend_Acl_Resource('user/login'),'guest_allow');
		
                $this->allow('guest','user/register');
                $this->allow('guest','user/activate');
                $this->allow('guest','user/restorepasswd');

		// user resources
		$this->add(new Zend_Acl_Resource('user_allow'));
		$this->add(new Zend_Acl_Resource('user/view'), 'user_allow');
                $this->add(new Zend_Acl_Resource('user/message'), 'user_allow');
                $this->add(new Zend_Acl_Resource('user/settings'), 'user_allow');
                $this->add(new Zend_Acl_Resource('user/edit'), 'user_allow');
		$this->add(new Zend_Acl_Resource('user/logout'), 'user_allow');
                $this->add(new Zend_Acl_Resource('chat/addmessage'), 'user_allow');
                
                $this->deny('user','user/register');
                $this->deny('user','user/activate');
                $this->deny('user','user/restorepasswd');

		// admin resources
		$this->add(new Zend_Acl_Resource('admin_allow'));
                $this->add(new Zend_Acl_Resource('article/add'), 'admin_allow');
                $this->add(new Zend_Acl_Resource('article/edit'), 'admin_allow');
                $this->add(new Zend_Acl_Resource('admin/articles'), 'admin_allow');
				
		// master resources
		$this->add(new Zend_Acl_Resource('master_allow'));
		$this->add(new Zend_Acl_Resource('admin/index'),'master_allow');

		//Выставляем права, по-умолчанию всё запрещено
		//this->deny('user', 'user_deny', 'show');
		$this->allow('guest', 'guest_allow', 'show');
		$this->allow('user', 'user_allow', 'show');
		$this->allow('admin','admin_allow', 'show');
		$this->allow('master','master_allow', 'show');
	}

	public function can($privilege='show'){
		//Инициируем ресурс
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$resource = $request->getControllerName() . '/' . $request->getActionName();
		//Если ресурс не найден закрываем доступ
		if (!$this->has($resource))
			return true;
		
                //Инициируем роль
                if (Zend_Auth::getInstance()->hasIdentity()) {
                    $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
                    // get role_id from user table
                    $mapper = new Application_Model_UserMapper();
                    $role_id = $mapper->getUserRole($storage_data->id);
                    // get role name from role table
                    $mapper = new Application_Model_RoleMapper();
                    $role = $mapper->getRoleName($role_id);
                } else {
                    $role = 'guest';
                }
                
		return $this->isAllowed($role, $resource, $privilege);
	}
}