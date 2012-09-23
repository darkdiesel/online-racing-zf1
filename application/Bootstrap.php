<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initDoctype()
    {
		$this->bootstrap('view');
        $view = $this->getResource('view');
        //$view->doctype('XHTML1_STRICT');
        $view->doctype('HTML5');
    }
	
	protected function _initView()
    {
        $view = new Zend_View();

        $request = Zend_Controller_Front::getInstance()->getRequest();
                 
        // setting the site in the title; possibly in the layout script:
        $view->headTitle('Online-Racing.net');

        // setting a separator string for segments:
        $view->headTitle()->setSeparator(' - ');

        //head meta
        
        $view->headMeta()
                 ->appendName('description', 'Site for online racing competition with rFactor games and mods and F1 news.')
                 ->appendName('keywords','F1, F-1, Online F1, F1 News, Формула-1, Formula One, rfactor, Online-Racing, Онлайн гонки, RFT, Sim Racing, Race,
                               Гонки, Новости Формулы 1, сим-рейсинг, championship, formula1 скачать, русификатор, rfactor lite')
                 ->appendName('subject', 'Sim racing')
                 ->appendName('title', 'Online-Racing')
                 ->appendName('revisit', '5 days')
                 ->appendName('resource-type', 'document')
                 ->appendName('Copyright', 'Igor Peshkov. Copyright 2012')
                 ->appendName('Author', 'Igor Peshkov. Copyright 2012')
                 ->appendName('reply-to', 'Igor.Peshkov@gmail.com')
                 ->appendName('Generator', 'Sublime Text 2, phpStorm, notepad++')
                 ->setHttpEquiv('X-UA-Compatible','IE=edge')
                 ->setHttpEquiv('X-UA-Compatible','IE=EmulateIE9')
                 ->setHttpEquiv('Cache-Control','no-store')
                 ->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
                 ->appendHttpEquiv('Content-Language', 'en-US')
                 ->appendHttpEquiv('Content-Language', 'ru');
                 /*->appendName('keywords', 'rfactor 2, CARS, C.A.R.S, rfactor, ISI, SMS, F1 2011, F1 2012, WRC 3, WRC 2, WRC, Milestone, Reiza Studios, 
                    Formula 1, iRacing, Petrov, Game Stock Car, Fsone, MMG, CTDP, F1, Hamilton, Raikkonen, NFS, Starcraft 2, Kart Racing Pro, LFS, f1, 
                    championship, rfactor, ef1c ,f1 challenge 99-02 скачать, formula1 скачать, f1 challenge 99-02 ,rfactor лига, f1 2009, игра, 
                    телеметрия, игра, f1 2009 патч, русификатор, f1rft 2009, rfactor lite, сим-рейсинг ,F-1Mania MOD 2009, Need for Speed: Shift, 
                    F1 2005, CTDP 1.20 FIA Gala F1, Clip online, виртуальные гонки');*/

        $view->headLink()->appendStylesheet($view->baseUrl("css/bootstrap.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/style.css"));
        //$view->headLink()->appendStylesheet($view->baseUrl("css/admin_menu.css"));
		$view->headLink()->appendStylesheet($view->baseUrl("css/main_menu.css"));
		$view->headLink()->appendStylesheet($view->baseUrl("css/user_toolbar.css"));

		$view->headScript()->appendFile($view->baseUrl("js/jquery-1.8.2.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/bootstrap.min.js"));
        
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lavalamp.my.js"));
        
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lwtCountdown.js"));

        // All Common scripts
        $view->headScript()->appendFile($view->baseUrl("js/my_js.js"));
				
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