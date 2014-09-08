<?php

class Peshkov_Form_RacingSeries_Edit extends Peshkov_Form_RacingSeries_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminRacingSeriesIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'id', 'racingSeriesID' => $request->getParam('racingSeriesID')),
            'adminRacingSeriesID'
        );

        $adminRacingSeriesEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'edit', 'racingSeriesID' => $request->getParam('racingSeriesID')),
            'adminRacingSeriesAction'
        );

        $this->setAttrib('id', 'racing-series-edit')
            ->setName('racingSeriesEdit')
            ->setAction($adminRacingSeriesEditUrl);

        $this->getElement('Name')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('racingSeriesID'));

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminRacingSeriesIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}