<?php

class Admin_RaceController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Гонки'));
    }

    public function allAction()
    {

    }
}