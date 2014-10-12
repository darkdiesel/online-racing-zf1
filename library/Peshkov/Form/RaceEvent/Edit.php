<?php

class Peshkov_Form_RaceEvent_Edit extends Peshkov_Form_RaceEvent_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $defaultRaceEventIDUrl = $this->getView()->url(
            array('default' => 'default', 'controller' => 'race-event', 'action' => 'id', 'raceEventID' => $request->getParam('raceEventID')),
            'defaultRaceEventID'
        );

        $adminRaceEventEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'race-event', 'action' => 'edit', 'raceEventID' => $request->getParam('raceEventID')),
            'adminRaceEventAction'
        );

        $this->setAttrib('id', 'race-event-edit')
            ->setName('raceEventEdit')
            ->setAction($adminRaceEventEditUrl);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$defaultRaceEventIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}