<?php

class Peshkov_Form_Country_Edit extends Peshkov_Form_Country_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        $adminCountryIDUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'country', 'action' => 'id', 'countryID' => $request->getParam('countryID')),
            'adminCountryID'
        );

        $adminCountryEditUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'country', 'action' => 'edit', 'countryID' => $request->getParam('countryID')),
            'adminCountryAction'
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