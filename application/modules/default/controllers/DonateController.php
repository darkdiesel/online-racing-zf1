<?php

class DonateController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Помощь проекту'));
    }

    public function indexAction(){
        // Set head and page titles
        $this->view->pageTitle($this->view->translate('Помощь проекту'));
    }
}