<?php

class Peshkov_Form_Post_Edit extends Peshkov_Form_Post_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $defaultPostIDUrl = $this->getView()->url(
            array('default' => 'default', 'controller' => 'post', 'action' => 'id', 'postID' => $request->getParam('postID')),
            'defaultPostID'
        );

        $adminPostEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post', 'action' => 'edit', 'postID' => $request->getParam('postID')),
            'adminPostAction'
        );

        $this->setAttrib('id', 'post-edit')
            ->setName('postEdit')
            ->setAction($adminPostEditUrl);

           //TODO: Uncoment this code for allow upload to server post image
//        $this->getElement('LogoUrl')->setRequired(false);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$defaultPostIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}