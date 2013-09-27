<?php

class Acl extends Zend_Acl
{

    public function __construct()
    {
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
	$this->add(new Zend_Acl_Resource('index/index'), 'guest_allow');
	$this->add(new Zend_Acl_Resource('user/login'), 'guest_allow');

	$this->add(new Zend_Acl_Resource('league/id'), 'guest_allow');
	$this->add(new Zend_Acl_Resource('league/all'), 'guest_allow');

	$this->add(new Zend_Acl_Resource('game/all'), 'guest_allow');
	$this->add(new Zend_Acl_Resource('game/id'), 'guest_allow');

	$this->allow('guest', 'user/register');
	$this->allow('guest', 'user/activate');
	$this->allow('guest', 'user/restorepasswd');

	// user resources
	$this->add(new Zend_Acl_Resource('user_allow'));
	$this->add(new Zend_Acl_Resource('default/user/id'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/message'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/settings'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/edit'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/logout'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/chat/addmessage'), 'user_allow');

	//$this->deny('user', 'user/register');
	//$this->deny('user', 'user/activate');
	//$this->deny('user', 'user/restore-passwd');
	//$this->deny('user', 'user/set-restore-passwd');
	// admin resources
	$this->add(new Zend_Acl_Resource('admin_allow'));

	$this->add(new Zend_Acl_Resource('default/post/add'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('default/post/edit'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('default/post/delete'), 'admin_allow');

	$this->add(new Zend_Acl_Resource('article-type/id'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('article-type/all'), 'admin_allow');

	$this->add(new Zend_Acl_Resource('default/championship/add'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('default/championship/edit'), 'admin_allow');

	$this->add(new Zend_Acl_Resource('default/championship/team-add'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('default/championship/team-edit'), 'admin_allow');

	$this->add(new Zend_Acl_Resource('default/championship/driver-add'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('default/championship/driver-edit'), 'admin_allow');
	$this->add(new Zend_Acl_Resource('default/championship/driver-delete'), 'admin_allow');

	//$this->add(new Zend_Acl_Resource('race/add'), 'admin_allow');
	// master resources
	$this->add(new Zend_Acl_Resource('master_allow'));

	// LEAGUE
	$this->add(new Zend_Acl_Resource('default/league/add'), 'master_allow');
	$this->add(new Zend_Acl_Resource('default/league/edit'), 'master_allow');
	$this->add(new Zend_Acl_Resource('default/league/delete'), 'master_allow');

	// TEAM 
	$this->add(new Zend_Acl_Resource('default/team/add'), 'master_allow');
	$this->add(new Zend_Acl_Resource('default/team/edit'), 'master_allow');
	$this->add(new Zend_Acl_Resource('default/team/delete'), 'master_allow');

	// COUNTRY
	$this->add(new Zend_Acl_Resource('admin/country/id'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/country/add'), 'master_allow');

	// EVENT
	$this->add(new Zend_Acl_Resource('admin/event/add'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/event/edit'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/event/delete'), 'master_allow');

	// RESOURCE
	$this->add(new Zend_Acl_Resource('admin/resource/id'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/resource/all'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/resource/add'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/resource/edit'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/resource/delete'), 'master_allow');

	// CONTENT-TYPE
	$this->add(new Zend_Acl_Resource('admin/content-type/add'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/content-type/edit'), 'master_allow');
	$this->add(new Zend_Acl_Resource('admin/content-type/delete'), 'master_allow');

	// ARTICLE-TYPE
	$this->add(new Zend_Acl_Resource('default/article-type/add'), 'master_allow');
	$this->add(new Zend_Acl_Resource('default/article-type/edit'), 'master_allow');
	$this->add(new Zend_Acl_Resource('default/article-type/delete'), 'master_allow');

	// CHAMPIONSHIP
	$this->add(new Zend_Acl_Resource('default/championship/delete'), 'master_allow');

	// RACE
	$this->add(new Zend_Acl_Resource('default/race/add'), 'master_allow');

	// ADMIN
	$this->add(new Zend_Acl_Resource('admin/index/index'), 'admin_allow');

	//Выставляем права, по-умолчанию всё запрещено
	//this->deny('user', 'user_deny', 'show');
	$this->allow('guest', 'guest_allow', 'show');
	$this->allow('user', 'user_allow', 'show');
	$this->allow('admin', 'admin_allow', 'show');
	$this->allow('master', 'master_allow', 'show');
    }

    public function can($privilege = 'show')
    {
	//Инициируем ресурс
	$request = Zend_Controller_Front::getInstance()->getRequest();
	$resource = "{$request->getModuleName()}/{$request->getControllerName()}/{$request->getActionName()}";
	//Если ресурс не найден закрываем доступ
	if (!$this->has($resource))
	    return true;

	//Инициируем роль
	if (Zend_Auth::getInstance()->hasIdentity()) {
	    $storage_data = Zend_Auth::getInstance()->getStorage()->read();
	    // get role name for current user
	    $user = new Application_Model_DbTable_User();
	    $role = $user->getUserRoleName($storage_data->id);
	} else {
	    $role = 'guest';
	}
	return $this->isAllowed($role, $resource, $privilege);
    }

    public function checkUserAccess($resource)
    {
	if (!$this->has($resource))
	    return true;

	$privilege = 'show';

	$role = $this->getRole();
	return $this->isAllowed($role, $resource, $privilege);
    }
    
    // function return user role
    public function getUser()
    {
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