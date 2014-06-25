<?php

class App_Plugin_Module_Forum extends Zend_Controller_Plugin_Abstract {

	private $_bootstrap;

	function __construct($bootstrap) {
		$this->_bootstrap = $bootstrap;
	}

	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		if ('forum' != $request->getModuleName()) {
			// If not in this module, return early
			return;
		}

		$this->_bootstrap->bootstrap('layout');
		$layout = $this->_bootstrap->getResource('layout');
		$view = $layout->getView();

		$view->headTitle($view->translate('Форум'));

		// Change layout
		Zend_Layout::getMvcInstance()->setLayout('forum');
	}

}
