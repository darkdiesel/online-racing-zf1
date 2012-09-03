<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initDoctype()
    {
		$this->bootstrap('view');
        $view = $this->getResource('view');
        //$view->doctype('XHTML1_STRICT');
    }
	
	protected function _initView()
    {
        $view = new Zend_View();
		
        $view->headTitle('Online-Racing');
        $view->headLink()->appendStylesheet($view->baseUrl("css/bootstrap.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/style.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/admin_menu.css"));
		$view->headLink()->appendStylesheet($view->baseUrl("css/main_menu.css"));
		$view->headLink()->appendStylesheet($view->baseUrl("css/user_toolbar.css"));
        
		// start slider css
		$view->headLink()->appendStylesheet($view->baseUrl("css/skitter.styles.css"));
		// end slider css
		
		$view->headScript()->appendFile($view->baseUrl("js/jquery-1.8.0.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/bootstrap.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lavalamp.my.js"));
		
		// start slider js
		$view->headScript()->appendFile($view->baseUrl("js/jquery.easing.1.3.js"));
		$view->headScript()->appendFile($view->baseUrl("js/jquery.animate_colors.min.js"));
		$view->headScript()->appendFile($view->baseUrl("js/jquery.skitter.js"));
        $view->headScript()->appendFile($view->baseUrl("js/my_js.js"));
		// end slider js
		
        // ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        $viewRenderer->setView($view);
 
        return $view;
    }
	
	

}