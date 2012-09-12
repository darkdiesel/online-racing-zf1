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
        //$view->headLink()->appendStylesheet($view->baseUrl("css/admin_menu.css"));
		$view->headLink()->appendStylesheet($view->baseUrl("css/main_menu.css"));
		$view->headLink()->appendStylesheet($view->baseUrl("css/user_toolbar.css"));

		// start slider css
		$view->headLink()->appendStylesheet($view->baseUrl("css/skitter.styles.css"));
		// end slider css
		
		$view->headScript()->appendFile($view->baseUrl("js/jquery-1.8.0.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/bootstrap.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lavalamp.my.js"));
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lwtCountdown-1.0.js"));

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
	
	// Initialisation Authorisation
	public function _initAuth(){
		$auth = Zend_Auth::getInstance();
		$auth->setStorage(new Zend_Auth_Storage_Session('online-racing'));
		$data = $auth->getStorage()->read();
		if (!isset($data->status)){
			$storage_data = new stdClass();
			$storage_data->status = 'guest';
			$auth->getStorage()->write($storage_data);
		}
	}

	public function _initAcl(){
		Zend_Loader::loadClass('Acl');
		Zend_Loader::loadClass('CheckAccess');
		Zend_Controller_Front::getInstance()->registerPlugin(new CheckAccess());
		return new Acl();
	}
	
	public function _initLogger()
    {
		$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/application.log');
        $logger = new Zend_Log($writer);
        Zend_Registry::set( 'logger', $logger );
	}
	
	public function _initTranslate()
    {
        $locales = array(
            'en_US','ru_RU'
        );
        $locale = new Zend_Locale();
        /*if(!in_array($locale,$locales)) {
            $locale = "en_US";
        }
        $translate = new Zend_Translate(
            array(
                'adapter' => 'gettext',
                'content' => APPLICATION_PATH . '/languages/' . $locale . '.mo',
                'locale'  => $locale
            )
        );

        Zend_Registry::set('Zend_Translate', $translate);*/

    }
}