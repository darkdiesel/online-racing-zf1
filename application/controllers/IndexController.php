<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
		// Js for Skitter slider
		$this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.easing.1.3.js"));
		$this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.animate_colors.min.js"));
		$this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.skitter.js"));

		// Css for skitter slider
		$this->view->headLink()->appendStylesheet($this->view->baseUrl("css/skitter.styles.css"));
    }
}