<?php

class Peshkov_Form_Race_Edit extends Peshkov_Form_Race_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $defaultRaceIDUrl = $this->getView()->url(
            array('default' => 'default', 'controller' => 'race', 'action' => 'id', 'raceID' => $request->getParam('raceID')),
            'defaultRaceID'
        );

        $adminRaceEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'race', 'action' => 'edit', 'raceID' => $request->getParam('raceID')),
            'adminRaceAction'
        );

        $this->setAttrib('id', 'race-edit')
            ->setName('raceEdit')
            ->setAction($adminRaceEditUrl);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$defaultRaceIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}