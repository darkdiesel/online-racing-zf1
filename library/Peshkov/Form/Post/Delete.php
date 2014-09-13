<?php

class Peshkov_Form_Post_Delete extends Zend_Form
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

        $defaultPostIDUrl = $this->getView()->url(
            array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'postID' => $request->getParam('postID')),
            'defaultPostID'
        );

        $adminPostDeleteUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'post', 'action' => 'delete', 'postID' => $request->getParam('postID')),
            'adminPostAction'
        );

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'post-delete'
            )
        )
            ->setName('postDelete')
            ->setAction($adminPostDeleteUrl)
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Удалить'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='{$defaultPostIDUrl}'")
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
