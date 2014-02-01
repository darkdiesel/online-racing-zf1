<?php

class App_Plugin_Module_Admin extends Zend_Controller_Plugin_Abstract {

	private $_bootstrap;

	function __construct($bootstrap) {
		$this->_bootstrap = $bootstrap;
	}

	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		if ('admin' != $request->getModuleName()) {
			// If not in this module, return early
			return;
		}

		$this->_bootstrap->bootstrap('layout');
		$layout = $this->_bootstrap->getResource('layout');
		$view = $layout->getView();

		$view->headTitle($view->translate('Панель Администрирования'));

		/* ===== [BLOCK DISPLAY SETTINGS] ===== */
		$view->page_scroller_block = true; // back to top block

		$view->addHelperPath('App/View/Helper', 'App_View_Helper');

		// [CSS Minify]
		$view->minifyHeadLink()->appendStylesheet('/css/layout-admin.css');

		// Page scroller block
		if ($view->page_scroller_block) {
			$view->headScript()->appendFile("/library/jquery.page-scroller/jquery.page-scroller.js");
			$view->minifyHeadLink()->appendStylesheet("/library/jquery.page-scroller/css/page-srcoller.css");
		}

		// Change layout
		Zend_Layout::getMvcInstance()->setLayout('admin');
	}

}
