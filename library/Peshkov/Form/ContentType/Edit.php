<?php

class Peshkov_Form_ContentType_Edit extends Peshkov_Form_ContentType_Add
{
    public function init()
    {
        // get parent form
        parent::init();

        $this->getElement('Submit')->setLabel('Сохранить');
    }
}