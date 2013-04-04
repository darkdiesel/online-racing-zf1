<?php

class RaceController extends App_Controller_FirstBootController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Гонка'));
    }

    public function idAction()
    {
        // action body
    }


}

