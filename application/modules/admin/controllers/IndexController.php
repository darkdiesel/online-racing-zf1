<?php

class Admin_IndexController extends App_Controller_FirstBootController
{

    public function init()
    {
	parent::init();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->view->headTitle($this->view->translate('Панель администрирования'));
    }


}
