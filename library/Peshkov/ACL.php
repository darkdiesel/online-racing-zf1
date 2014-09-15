<?php

class Peshkov_Acl extends Zend_Acl
{

    protected $_roles;
    protected $_resources;

    const RESOURCE_SEPARATOR = '::';

    public function __construct()
    {
        $this->initRoles();

    }

    protected function initRoles()
    {
        $query = Doctrine_Query::create()
            ->from('Default_Model_Role r')
            ->orderBy('r.ID DESC');

        $roleResult = $query->fetchArray();

        if ($roleResult) {
            $this->_roles = array();
            foreach ($roleResult as $role) {
                $this->_roles[$role['ID']] = array(
                    'ID' => $role['ID'],
                    'Name' => $role['Name'],
                    'ParentRoleID' => $role['ParentRoleID'],
                    'Added' => 0,
                );
            }

            // Add roles to ACL
            foreach ($this->_roles as $role) {
                if (!$this->_roles[$role['ID']]['Added']) {
                    $this->addRecursiveRole($role['ID'], $role['ParentRoleID']);
                }
            }
        }
    }

    protected function addRecursiveRole($roleID = null, $parentRoleID = null)
    {
        if ($parentRoleID) {
            if ($this->_roles[$parentRoleID]['Added']) {
                $this->addRole(new Zend_Acl_Role($this->_roles[$roleID]['Name']), $this->_roles[$parentRoleID]['Name']);
                $this->_roles[$roleID]['Added'] = 1;
            } else {
                $this->addRecursiveRole($parentRoleID, $this->_roles[$roleID]['ParentRoleID']);
                $this->addRecursiveRole($roleID);
            }
        } else {
            $this->addRole(new Zend_Acl_Role($this->_roles[$roleID]['Name']));
            $this->_roles[$roleID]['Added'] = 1;
        }
    }
}