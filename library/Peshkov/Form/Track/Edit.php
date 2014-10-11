<?php

class Peshkov_Form_Track_Edit extends Peshkov_Form_Track_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminTrackIDUrl = $this->getView()->url(
            array('default' => 'admin', 'controller' => 'track', 'action' => 'id', 'trackID' => $request->getParam('trackID')),
            'adminTrackID'
        );

        $adminTrackEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'track', 'action' => 'edit', 'trackID' => $request->getParam('trackID')),
            'adminTrackAction'
        );

        $this->setAttrib('id', 'track-edit')
            ->setName('trackEdit')
            ->setAction($adminTrackEditUrl);

        $this->getElement('SchemeUrl')->setRequired(false);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminTrackIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}