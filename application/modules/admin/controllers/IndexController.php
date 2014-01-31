<?php

class Admin_IndexController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		/* Initialize action controller here */
	}

	public function indexAction() {
		$this->view->headTitle($this->view->translate('Главная'));
	}

}
