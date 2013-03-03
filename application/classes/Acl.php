<?php

class Acl extends Zend_Acl {

    public function __construct() {
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
        $this->add(new Zend_Acl_Resource('user/view'), 'user_allow');
        $this->add(new Zend_Acl_Resource('user/message'), 'user_allow');
        $this->add(new Zend_Acl_Resource('user/settings'), 'user_allow');
        $this->add(new Zend_Acl_Resource('user/edit'), 'user_allow');
        $this->add(new Zend_Acl_Resource('user/logout'), 'user_allow');
        $this->add(new Zend_Acl_Resource('chat/addmessage'), 'user_allow');

        //$this->deny('user', 'user/register');
        //$this->deny('user', 'user/activate');
        //$this->deny('user', 'user/restore-passwd');
        //$this->deny('user', 'user/set-restore-passwd');

        // admin resources
        $this->add(new Zend_Acl_Resource('admin_allow'));

        $this->add(new Zend_Acl_Resource('article/add'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('article/edit'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('article/delete'), 'admin_allow');

        $this->add(new Zend_Acl_Resource('admin/articles'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('admin/index'), 'admin_allow');

        $this->add(new Zend_Acl_Resource('article-type/id'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('article-type/all'), 'admin_allow');

        $this->add(new Zend_Acl_Resource('game/add'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('game/edit'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('game/delete'), 'admin_allow');
        
        $this->add(new Zend_Acl_Resource('championship/edit'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('championship/delete'), 'admin_allow');
        
        $this->add(new Zend_Acl_Resource('event/add'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('event/edit'), 'admin_allow');
        $this->add(new Zend_Acl_Resource('event/delete'), 'admin_allow');

        // master resources
        $this->add(new Zend_Acl_Resource('master_allow'));
        $this->add(new Zend_Acl_Resource('article-type/add'), 'master_allow');
        $this->add(new Zend_Acl_Resource('article-type/edit'), 'master_allow');
        $this->add(new Zend_Acl_Resource('article-type/delete'), 'master_allow');

        $this->add(new Zend_Acl_Resource('league/add'), 'master_allow');
        $this->add(new Zend_Acl_Resource('league/edit'), 'master_allow');
        $this->add(new Zend_Acl_Resource('league/delete'), 'master_allow');
        
        $this->add(new Zend_Acl_Resource('team/add'), 'master_allow');
        $this->add(new Zend_Acl_Resource('team/edit'), 'master_allow');
        $this->add(new Zend_Acl_Resource('team/delete'), 'master_allow');
        
        $this->add(new Zend_Acl_Resource('country/id'), 'master_allow');
        $this->add(new Zend_Acl_Resource('country/add'), 'master_allow');
        
        $this->add(new Zend_Acl_Resource('championship/add'), 'master_allow');
        $this->add(new Zend_Acl_Resource('championship/addteam'), 'master_allow');
        $this->add(new Zend_Acl_Resource('championship/editteam'), 'master_allow');

        //Выставляем права, по-умолчанию всё запрещено
        //this->deny('user', 'user_deny', 'show');
        $this->allow('guest', 'guest_allow', 'show');
        $this->allow('user', 'user_allow', 'show');
        $this->allow('admin', 'admin_allow', 'show');
        $this->allow('master', 'master_allow', 'show');
    }

    public function can($privilege = 'show') {
        //Инициируем ресурс
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $resource = $request->getControllerName() . '/' . $request->getActionName();
        //Если ресурс не найден закрываем доступ
        if (!$this->has($resource))
            return true;

        //Инициируем роль
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session('online-racing'));
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
            // get role name for current user
            $user = new Application_Model_DbTable_User();
            $role = $user->getUserRoleName($storage_data->id);
        } else {
            $role = 'guest';
        }
        return $this->isAllowed($role, $resource, $privilege);
    }
    
    public function checkUserAccess($resource){
        if (!$this->has($resource))
            return true;
        
        $privilege = 'show';
        
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session('online-racing'));
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
            // get role name for current user
            $user = new Application_Model_DbTable_User();
            $role = $user->getUserRoleName($storage_data->id);
        } else {
            $role = 'guest';
        }
        return $this->isAllowed($role, $resource, $privilege);
        
    }

}