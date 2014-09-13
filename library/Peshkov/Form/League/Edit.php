<?php

class Peshkov_Form_League_Edit extends Peshkov_Form_League_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminLeagueIDUrl = $this->getView()->url(
            array('default' => 'default', 'controller' => 'league', 'action' => 'id', 'leagueID' => $request->getParam('leagueID')),
            'defaultLeagueID'
        );

        $adminLeagueEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'league', 'action' => 'edit', 'leagueID' => $request->getParam('leagueID')),
            'adminLeagueAction'
        );

        $this->setAttrib('id', 'league-edit')
            ->setName('leagueEdit')
            ->setAction($adminLeagueEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('leagueID'));

        $this->getElement('UrlImageLogo')->setRequired(false);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminLeagueIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}