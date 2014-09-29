<?php

class Peshkov_Form_Role_Edit extends Peshkov_Form_Role_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminRoleIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id', 'RoleID' => $request->getParam('RoleID')),
            'adminRoleID'
        );

        $adminRoleEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'content-type', 'action' => 'edit', 'RoleID' => $request->getParam('RoleID')),
            'adminRoleAction'
        );

        $this->setAttrib('id', 'content-type-edit')
            ->setName('RoleEdit')
            ->setAction($adminRoleEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('RoleID'));

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminRoleIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}