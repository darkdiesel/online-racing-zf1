<?php

class Peshkov_Form_PostType_Edit extends Peshkov_Form_ContentType_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminPostTypeIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id', 'postTypeID' => $request->getParam('postTypeID')),
            'adminPostTypeID'
        );

        $adminPostTypeEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post-type', 'action' => 'edit', 'postTypeID' => $request->getParam('postTypeID')),
            'adminPostTypeAction'
        );

        $this->setAttrib('id', 'post-type-edit')
            ->setName('postTypeEdit')
            ->setAction($adminPostTypeEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('postTypeID'));

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminPostTypeIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}