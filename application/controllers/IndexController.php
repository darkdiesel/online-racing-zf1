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
                
                // jQuery UI
                $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery-ui-1.8.23.custom.min.js"));

		// Css for skitter slider
		$this->view->headLink()->appendStylesheet($this->view->baseUrl("css/skitter.styles.css"));
                
                // Css for jQuery UI
                $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/jquery-ui-1.8.23.custom.css"));

        // page title
        $this->view->headTitle('Главная');
    }
}