<?php

class Peshkov_Form_Country_Edit extends Peshkov_Form_Country_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminCountryIDUrl = $this->view->url(
            array('module' => 'admin', 'controller' => 'country', 'action' => 'id', 'countryID' => $request->getParam('countryID')),
            'adminCountryID'
        );

        $adminCountryEditUrl = $this->view->url(
            array('module' => 'admin', 'controller' => 'country', 'action' => 'edit', 'countryID' => $request->getParam('countryID')),
            $request
        );

        $this->setAttrib('id', 'content-type-edit')
            ->setName('contentTypeEdit')
            ->setAction($adminCountryEditUrl);

        $this->getElement('NativeName')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('countryID'));
        $this->getElement('EnglishName')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('countryID'));
        $this->getElement('Abbreviation')->getValidator('Db_NoRecordExists')->setExclude('ID != ' . $request->getParam('countryID'));

        $this->getElement('UrlImageRound')->setRequired(false);
        $this->getElement('UrlImageGlossyWave')->setRequired(false);

        $this->getElement('Cancel')->setAttrib('onClick', "location.href='{$adminCountryIDUrl}'");

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}