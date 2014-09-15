<?php

class Acl extends Zend_Acl
{

    protected $roles;
    protected $resources;

    const RESOURCE_SEPARATOR = '::';

    public function __construct()
    {
        // Init roles array from DB
        $this->getRolesArray();
        $this->initRoles();

        $this->getResourcesArray();
        $this->initResources();

        $this->deny();
//
        $this->initAccess();
    }

    protected function getRolesArray()
    {
        $query = Doctrine_Query::create()
            ->from('Default_Model_Role r')
            ->orderBy('r.ID DESC');

        $roleResult = $query->fetchArray();

        if ($roleResult) {
            $this->roles = array();
            foreach ($roleResult as $role) {
                $this->roles[$role->ID] = array(
                    'id' => $role->ID,
                    'name' => $role->Name,
                    'parent_role_id' => $role->ParentRoleID,
                    'added' => 0,
                );
            }
        }
    }

    protected function getResourcesArray()
    {
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

    protected function initRoles()
    {
        if ($this->roles) {
            foreach ($this->roles as $role) {
                if (!$this->roles[$role['id']]['added']) {
                    $this->addRecursiveRole($role['id']);
                }
            }
        }
    }

    protected function addRecursiveRole($role_id)
    {
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

    protected function initResources()
    {
        if ($this->resources) {
            foreach ($this->resources as $resource) {
                if (!$this->resources[$resource['id']]['added']) {
                    $this->addRecursiveResource($resource['id']);
                }
            }
        }
    }

    protected function addRecursiveResource($resource_id)
    {
        $this->add(new Zend_Acl_Resource($this->resources[$resource_id]['name']));
        $this->resources[$resource_id]['added'] = 1;
    }

    protected function initAccess()
    {
        $resource_access_db = new Application_Model_DbTable_ResourceAccess();
        $resource_access_data = $resource_access_db->getAll(FALSE, "all");

        if ($resource_access_data) {
            foreach ($resource_access_data as $resource_access) {
                if ($resource_access->allow) {
                    $operation = 'allow';
                } else {
                    $operation = 'deny';
                }

                $this->$operation($resource_access->role_name, $resource_access->resource_name, $resource_access->privilege_name);
            }
        }
    }

    public function can()
    {
        //Инициируем ресурс
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $resource = $request->getModuleName() . self::RESOURCE_SEPARATOR . $request->getControllerName();
        $privilege = $request->getActionName();

        //Если ресурс не найден закрываем доступ
        if (!$this->has($resource))
            return true;

        //Inicialize role
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $storageData = Zend_Auth::getInstance()->getStorage()->read();

            $user_role_db = new Application_Model_DbTable_UserRole();
            $role = $user_role_db->getItem(array('UserID' => $storageData->id))->role_name;
        } else {
            $role = 'guest';
        }
        return $this->isAllowed($role, $resource, $privilege);
    }

    public function checkUserAccess($resource, $privilege)
    {
        if (!$this->has($resource))
            return true;

        //Inicialize role
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $storageData = Zend_Auth::getInstance()->getStorage()->read();

            $user_role_db = new Application_Model_DbTable_UserRole();
            $role = $user_role_db->getItem(array('UserID' => $storageData->id))->role_name;
        } else {
            $role = 'guest';
        }

        return $this->isAllowed($role, $resource, $privilege);
    }

    // function return user role
    public function getUser()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $storageData = Zend_Auth::getInstance()->getStorage()->read();

            $query = Doctrine_Query::create()
                ->from('Default_Model_User u')
                ->leftJoin('u.UserRole ur')
                ->leftJoin('ur.Role r')
                ->where('u.ID = ?', $storageData['id']);

            $userResult = $query->fetchArray();

            if (count($userResult) == 1) {
                $role = $userResult[0]['Role']['Name'];
            } else {
                $this->messages->addError($this->view->translate('Запрашиваемый пользователь не найден!'));
            }
        } else {
            $role = 'guest';
        }

        return $role;
    }

}
