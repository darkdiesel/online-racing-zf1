<?php

class App_Plugin_Module_Default extends Zend_Controller_Plugin_Abstract
{

    private $_bootstrap;

    function __construct($bootstrap)
    {
	$this->_bootstrap = $bootstrap;
    }

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
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
	$view->ft_user_counters_block = true; // user counters block

	/* [FOOTER COUNTER] */
	$view->uc_live_internet_block = true; // liveinternet user counter block
	$view->uc_mail_ru_block = true; // mail.ru user counter block
	$view->uc_rambler100_block = true; // rambler 100 user counter block
	$view->uc_yandex_block = true; // yandex user counter block

	$view->addHelperPath('App/View/Helper', 'App_View_Helper');

	// [CSS Minify]
	$view->minifyHeadLink()->appendStylesheet('/css/style.css');
	$view->minifyHeadLink()->appendStylesheet('/css/user_toolbar.css');
	$view->minifyHeadLink()->appendStylesheet('/css/forms.css');
	$view->minifyHeadLink()->appendStylesheet('/css/articles.css');
	$view->minifyHeadLink()->appendStylesheet('/css/items.css');
	$view->minifyHeadLink()->appendStylesheet('/css/user.css');

	// [CHAT CSS]
	if ($view->ls_chat_block) {
	    $view->minifyHeadLink()->appendStylesheet($view->baseUrl("css/chat.css"));
	}
	if ($view->slide_out_tabs_block) {
	    $view->minifyHeadLink()->appendStylesheet($view->baseUrl("css/slide_out_tabs_block.css"));
	    $view->headScript()->appendFile($view->baseUrl("js/slide_out_tabs_block.js"));
	}

	// Page scroller block
	if ($view->page_scroller_block) {
	    $view->headScript()->appendFile($view->baseUrl("js/page_scroller.js"));
	    $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/page_srcoller.css'));
	}

	// Share block script
	if ($view->share_block) {
	    $view->headScript()->appendFile($view->baseUrl("js/share.js"));
	}

	// Script for count down block
	if ($view->ls_next_event_block) {
	    $view->headScript()->appendFile($view->baseUrl("libraries/jquery.countdown/jquery.countdown.min.js"));
	    $view->headScript()->appendFile($view->baseUrl("libraries/jquery.countdown/jquery.countdown-ru.js"));
	    $view->headLink()->appendStylesheet($view->baseUrl("libraries/jquery.countdown/css/jquery.countdown.css"));
	}

	if ((Zend_Auth::getInstance()->hasIdentity()) && ($view->ls_chat_block)) {
	    //chat script
	    $view->headScript()->appendFile($view->baseUrl("js/chat.js"));
	}

	// Change layout
	Zend_Layout::getMvcInstance()->setLayout('default');
    }

}

