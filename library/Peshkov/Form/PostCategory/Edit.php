<?php

class Peshkov_Form_PostCategory_Edit extends Peshkov_Form_PostCategory_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminPostCategoryIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post-category', 'action' => 'id', 'postCategoryID' => $request->getParam('postCategoryID')),
            'adminPostCategoryID'
        );

        $adminPostCategoryEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post-category', 'action' => 'edit', 'postCategoryID' => $request->getParam('postCategoryID')),
            'adminPostCategoryAction'
        );

        $this->setAttrib('id', 'post-category-edit')
            ->setName('postCategoryEdit')
            ->setAction($adminPostCategoryEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('postCategoryID'));

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminPostCategoryIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}