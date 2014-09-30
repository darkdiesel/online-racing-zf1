<?php

class Peshkov_Form_Role_Edit extends Peshkov_Form_Role_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminRoleIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'roleID' => $request->getParam('roleID')),
            'adminRoleID'
        );

        $adminRoleEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'role', 'action' => 'edit', 'roleID' => $request->getParam('roleID')),
            'adminRoleAction'
        );

        $this->setAttrib('id', 'role-edit')
            ->setName('RoleEdit')
            ->setAction($adminRoleEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('roleID'));
        $this->getElement('SystemName')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('roleID'));

        $this->getElement('ParentRoleID')->removeMultiOption($request->getParam('roleID'));

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminRoleIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}