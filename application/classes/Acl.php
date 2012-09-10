<?php
class Acl extends Zend_Acl {
	public function  __construct() {
		//Добавляем роли
		$this->addRole('guest');
		$this->addRole('user', 'guest');
		$this->addRole('admin', 'user');
		$this->addRole('master', 'admin');
				
		//Add resources
		// guest resources
		$this->add(new Zend_Acl_Resource('guest_allow'));
		$this->add(new Zend_Acl_Resource('index/index'),'guest_allow');
		$this->add(new Zend_Acl_Resource('user/login'),'guest_allow');
		$this->add(new Zend_Acl_Resource('user/registration'),'guest_allow');

		// user resources
		$this->add(new Zend_Acl_Resource('user_allow'));
		$this->add(new Zend_Acl_Resource('user/info'), 'user_allow');
		$this->add(new Zend_Acl_Resource('user/logout'), 'user_allow');

		// admin resources
		$this->add(new Zend_Acl_Resource('admin_allow'));
				
		// master resources
		$this->add(new Zend_Acl_Resource('master_allow'));
		$this->add(new Zend_Acl_Resource('admin/index'),'master_allow');

		//Выставляем права, по-умолчанию всё запрещено
		$this->deny(null, null, null);
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
		$storage_data = Zend_Auth::getInstance()->getStorage()->read();
		$role = array_key_exists('status', $storage_data)?$storage_data->status : 'guest';
		return $this->isAllowed($role, $resource, $privilege);
	}
}