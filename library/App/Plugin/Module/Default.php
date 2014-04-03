<?php

class App_Plugin_Module_Default extends Zend_Controller_Plugin_Abstract {

	private $_bootstrap;

	function __construct($bootstrap) {
		$this->_bootstrap = $bootstrap;
	}

	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		if ('default' != $request->getModuleName()) {
			// If not in this module, return early
			return;
		}

		$this->_bootstrap->bootstrap('layout');
		$layout = $this->_bootstrap->getResource('layout');
		$view = $layout->getView();

		/* ===== [BLOCK DISPLAY SETTINGS] ===== */
		/* [LAYOUT] */
		$view->share_block = false; // share block
		$view->back_to_top_btn = false; // back to top block
		$view->slide_out_tabs_block = true; // back to top block
		$view->page_scroller_block = true; // back to top block

		/* [LEFT SIDEBAR] */
		$view->ls_next_event_block = true; // count down block
		$view->ls_chat_block = true; // chat block
		$view->ls_user_map_block = true; // user map block
		$view->ls_online_radio_block = true; // search block

		/* [HEADER] */
		$view->hd_hot_lap_block = true; // hot lap block
		$view->hd_moving_text_block = true; // moving text block
		$view->hd_liveracers_block = true; // liveracers block (Liveracers.info)

		/* [FOOTER] */
		$view->ft_menu = true; // footer menu
		$view->ft_socialNetworks_block = true; // footer social networks block
		$view->ft_web_counters_block = true; // user counters block

		$view->addHelperPath('App/View/Helper', 'App_View_Helper');

		// [CSS]
		$view->minifyHeadLink()->appendStylesheet('/css/layout-default.css');
		$view->minifyHeadLink()->appendStylesheet('/css/user.css');

		// [JS]
		$view->headScript()->appendFile("/js/layout-default.js");

		// [CHAT CSS]
		if ($view->ls_chat_block) {
			$view->minifyHeadLink()->appendStylesheet("/css/chat.css");
		}
		if ($view->slide_out_tabs_block) {
			$view->minifyHeadLink()->appendStylesheet("/library/jquery.slide-out-tabs/css/slide-out-tabs.css");
			$view->headScript()->appendFile("/library/jquery.slide-out-tabs/jquery.slide-out-tabs.js");
		}

		// Page scroller block
		if ($view->page_scroller_block) {
			$view->headScript()->appendFile("/library/jquery.page-scroller/jquery.page-scroller.js");
			$view->minifyHeadLink()->appendStylesheet("/library/jquery.page-scroller/css/page-srcoller.css");
		}

		// Share block script
		if ($view->share_block) {
			$view->headScript()->appendFile("/js/share.js");
		}

		// Script for count down block
		if ($view->ls_next_event_block) {
			$view->headScript()->appendFile("/library/jquery.countdown/jquery.countdown.min.js");
			$view->headScript()->appendFile("/library/jquery.countdown/jquery.countdown-ru.js");
			$view->headLink()->appendStylesheet("/library/jquery.countdown/css/jquery.countdown.css");
		}

		if ((Zend_Auth::getInstance()->hasIdentity()) && ($view->ls_chat_block)) {
			//chat script
			$view->headScript()->appendFile("/js/chat.js");
		}

		// Change layout
		Zend_Layout::getMvcInstance()->setLayout('default');
	}

}
