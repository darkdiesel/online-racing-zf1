<?php

class TrackController extends App_Controller_FirstBootController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Трасса'));
    }

    public function idAction()
    {
        // action body
    }


}

