<?php

class Peshkov_Form_ContentType_Edit extends Peshkov_Form_ContentType_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminContentTypeIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id', 'contentTypeID' => $request->getParam('contentTypeID')),
            'adminContentTypeID'
        );

        $adminContentTypeEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'content-type', 'action' => 'edit', 'contentTypeID' => $request->getParam('contentTypeID')),
            'adminContentTypeAction'
        );

        $this->setAttrib('id', 'content-type-edit')
            ->setName('contentTypeEdit')
            ->setAction($adminContentTypeEditUrl);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminContentTypeIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}