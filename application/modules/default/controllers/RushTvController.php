<?php

class RushTvController extends App_Controller_LoaderController {

	public function indexAction() {
		// page title
		$this->view->headTitle($this->view->translate('Rush-TV'));
		$this->view->pageTitle($this->view->translate('Rush-TV'));

	}

}
