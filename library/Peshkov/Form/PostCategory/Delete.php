<?php

class Peshkov_Form_PostCategory_Delete extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminPostCategoryIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post-category', 'action' => 'id', 'postCategoryID' => $request->getParam('postCategoryID')),
            'adminPostCategoryID'
        );

        $adminPostCategoryDeleteUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post-category', 'action' => 'delete', 'postCategoryID' => $request->getParam('postCategoryID')),
            'adminPostCategoryAction'
        );

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'post-category-delete'
            )
        )
            ->setName('postCategoryDelete')
            ->setAction($adminPostCategoryDeleteUrl)
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Удалить'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='{$adminPostCategoryIDUrl}'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($submit)
            ->addElement($cancel);

        $this->addDisplayGroup(array(
            $this->getElement('Submit'),
            $this->getElement('Cancel')
        ), 'FormActions');

        $this->getDisplayGroup('FormActions')
            ->setOrder(100)
            ->setDecorators($this->getView()->getDecorator()->formActionsGroupDecorators());
    }

}