<?php

class Peshkov_Form_Track_Delete extends Zend_Form
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

        $adminTrackIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'trackID' => $request->getParam('trackID')),
            'adminTrackID'
        );

        $adminTrackDeleteUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'track', 'action' => 'delete', 'trackID' => $request->getParam('trackID')),
            'adminTrackAction'
        );

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'track-delete'
            )
        )
            ->setName('trackDelete')
            ->setAction($adminTrackDeleteUrl)
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Удалить'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='{$adminTrackIDUrl}'")
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