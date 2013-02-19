<?php

class Forum_IndexController extends App_Controller_FirstBootController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Форум'));
    }

    public function indexAction()
    {
        //$this->_helper->layout->disableLayout();
        // action body
    }


}

