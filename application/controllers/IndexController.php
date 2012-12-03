<?php

class IndexController extends App_Controller_FirstBootController {

    public function indexAction() {
        // js and css for Skitter slider
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.easing.1.3.js"));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.animate_colors.min.js"));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.skitter.min.js"));
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/skitter.styles.css"));

        // page title
        $this->view->headTitle($this->view->translate('Портал Онлай Скорости'));
    }

}