<?php

class Admin_FileManagerController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Файловый Менеджер'));
	}

	public function indexAction() {
		$this->view->pageTitle($this->view->translate('Файловый Менеджер'));
	}

}
