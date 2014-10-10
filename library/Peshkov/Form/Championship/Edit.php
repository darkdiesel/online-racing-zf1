<?php

class Peshkov_Form_Championship_Edit extends Peshkov_Form_Championship_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminChampionshipIDUrl = $this->getView()->url(
            array('default' => 'default', 'controller' => 'championship', 'action' => 'id', 'championshipID' => $request->getParam('championshipID')),
            'defaultChampionshipID'
        );

        $adminChampionshipEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'championship', 'action' => 'edit', 'championshipID' => $request->getParam('championshipID')),
            'adminChampionshipAction'
        );

        $this->setAttrib('id', 'championship-edit')
            ->setName('championshipEdit')
            ->setAction($adminChampionshipEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('championshipID'));

        $this->getElement('LogoUrl')->setRequired(false);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminChampionshipIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}