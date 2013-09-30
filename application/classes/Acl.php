<?php

class Acl extends Zend_Acl
{

    protected $roles;

    public function __construct()
    {
	// Init roles array from DB
	$this->getRolesArray();
	$this->initRoles();

	// resources
	$this->addResource(new Zend_Acl_Resource('default/user/register'));
	$this->addResource(new Zend_Acl_Resource('default/user/activate'));
	$this->addResource(new Zend_Acl_Resource('default/user/restorepasswd'));

	//Add resources
	// guest resources
	$this->add(new Zend_Acl_Resource('guest_allow'));
	$this->add(new Zend_Acl_Resource('default/index/index'), 'guest_allow');
	$this->add(new Zend_Acl_Resource('default/user/login'), 'guest_allow');

	$this->add(new Zend_Acl_Resource('default/league/id'), 'guest_allow');
	$this->add(new Zend_Acl_Resource('default/league/all'), 'guest_allow');

	$this->add(new Zend_Acl_Resource('default/game/all'), 'guest_allow');
	$this->add(new Zend_Acl_Resource('default/game/id'), 'guest_allow');

	$this->allow('guest', 'default/user/register');
	$this->allow('guest', 'default/user/activate');
	$this->allow('guest', 'default/user/restorepasswd');

	// user resources
	$this->add(new Zend_Acl_Resource('user_allow'));
	$this->add(new Zend_Acl_Resource('default/user/id'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/message'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/settings'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/edit'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/user/logout'), 'user_allow');
	$this->add(new Zend_Acl_Resource('default/chat/addmessage'), 'user_allow');

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
    
    protected function getRolesArray(){
	$role = new Application_Model_DbTable_Role();
	$role_all = $role->getAll('id, name, parent_role_id', array('parent_role_id', 'ASC'));

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
    
    protected function initRoles()
    {
	foreach ($this->roles as $role){
	    if (!$this->roles[$role['id']]['added']){
		$this->addRecursiveRole($role['id']);
	    }
	}
    }
    
    protected function addRecursiveRole($role_id)
    {
	$parent_role_id = $this->roles[$role_id]['parent_role_id'];

	if ($parent_role_id) {
	    if ($this->roles[$parent_role_id]['added']) {
		$this->addRole($this->roles[$role_id]['name'], $this->roles[$parent_role_id]['name']);
		$this->roles[$role_id]['added'] = 1;
	    } else {
		$this->addRecursiveRole($parent_role_id);
		$this->addRecursiveRole($role_id);
	    }
	} else {
	    $this->addRole($this->roles[$role_id]['name']);
	    $this->roles[$role_id]['added'] = 1;
	}
    }

    public function can($privilege = 'show')
    {
	//Инициируем ресурс
	$request = Zend_Controller_Front::getInstance()->getRequest();
	$resource = "{$request->getModuleName()}/{$request->getControllerName()}/{$request->getActionName()}";
	//Если ресурс не найден закрываем доступ
	if (!$this->has($resource))
	    return true;

	//Inicialize role
	if (Zend_Auth::getInstance()->hasIdentity()) {
	    $storage_data = Zend_Auth::getInstance()->getStorage()->read();

	    // get role name for current user
	    $user = new Application_Model_DbTable_User();
	    $role = $user->getUserRoleName($storage_data->id);

	    $user_role_db = new Application_Model_DbTable_UserRole();

	    $role = $user_role_db->getItem(array('user_id', $storage_data->id), array('id', 'nam'));
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
