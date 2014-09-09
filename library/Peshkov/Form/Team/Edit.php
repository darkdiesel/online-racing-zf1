<?php

class Peshkov_Form_Team_Edit extends Peshkov_Form_Team_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminTeamIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'team', 'action' => 'id', 'teamID' => $request->getParam('teamID')),
            'adminTeamID'
        );

        $adminTeamEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'team', 'action' => 'edit', 'teamID' => $request->getParam('teamID')),
            'adminTeamAction'
        );

        $this->setAttrib('id', 'team-edit')
            ->setName('teamEdit')
            ->setAction($adminTeamEditUrl);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminTeamIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}