<?php

class Peshkov_Form_Country_Edit extends Peshkov_Form_Country_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $this->getElement('UrlImageRound')->setRequired(false);
        $this->getElement('UrlImageGlossyWave')->setRequired(false);

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}