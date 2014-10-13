<?php

class Admin_IndexController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->view->headTitle($this->view->t('Панель управления'));
        $this->view->pageTitle($this->view->t('Панель управления'));
        $this->view->pageIcon('<i class="fa fa-dashboard"></i>');
    }

}
