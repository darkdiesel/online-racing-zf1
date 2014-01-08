<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    public function _initConfig()
    {
	$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV, array('allowModifications' => true));
	$config->domain = $_SERVER['HTTP_HOST'];
	$config->setReadOnly();
	Zend_Registry::set('config', $config);
    }

    protected function _initNameSpace()
    {
	Zend_Loader_Autoloader::getInstance()->registerNamespace('App');
    }

    protected function _initDb()
    {
	try {
	    $config = $this->getOptions();
	    $db = Zend_Db::factory($config['resources']['db']['adapter'], $config['resources']['db']['params']);
	    Zend_Db_Table::setDefaultAdapter($db);

	    /* $registry = Zend_Registry::getInstance();
	      $registry->configuration = $config;
	      $registry->dbAdapter = $db;
	      $registry->session = new Zend_Session_Namespace(); */
	} catch (Exception $e) {
	    exit($e->getMessage());
	}

	Zend_Registry::set('db', $db);
	return $db;
    }

    protected function _initSessions()
    {
	$this->bootstrap('session');
    }

    // Initialisation Authorisation
    public function _initAuth()
    {
	//Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session());
    }

    protected function _initSiteModules()
    {
	//Don't forget to bootstrap the front controller as the resource may not been created yet...  
	$this->bootstrap("frontController");
	$front = $this->getResource("frontController");

	//Add modules dirs to the controllers for default routes...  
	$front->addModuleDirectory(APPLICATION_PATH . '/modules');
    }

    protected function _initPlugins()
    {
	// plugin for view 
	$frontController = Zend_Controller_Front::getInstance();
	$frontController->registerPlugin(new App_Controller_Plugin_ViewSetup());

	$frontController->registerPlugin(new App_Plugin_SessionTrack());
    }

    protected function _initView()
    {
	$view = new Zend_View();

	$request = Zend_Controller_Front::getInstance()->getRequest();

	// [MAIN SITE TITLE SETTINGS]
	$view->headTitle('Online-Racing.net')
		->setSeparator(' :: '); // setting a separator string for segments

	$view->addHelperPath(APPLICATION_PATH . '/../library/App/View/Helper/', "App_View_Helper");

	// [HEAD META SETTINGS]
	$view->headMeta()
		->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
		->setHttpEquiv('X-UA-Compatible', 'IE=edge')
		->appendHttpEquiv('Content-Language', 'en-US')
		->appendHttpEquiv('Content-Language', 'ru')
		->appendName('description', 'Site for online racing competition with rFactor games and mods and F1 news.')
		->appendName('keywords', 'F1, F-1, Online F1, F1 News, Формула-1, Formula One, rfactor, Online-Racing, Онлайн гонки, RFT, Sim Racing, Race,
                               Гонки, Новости Формулы 1, сим-рейсинг, championship, formula1 скачать, русификатор, rfactor lite')
		->appendName('subject', 'Sim racing')
		->appendName('title', 'Online-Racing')
		->appendName('revisit', '5 days')
		->appendName('resource-type', 'document')
		->appendName('Copyright', 'Igor Peshkov. Copyright 2012')
		->appendName('Author', 'Igor Peshkov. Copyright 2012')
		->appendName('reply-to', 'Igor.Peshkov@gmail.com')
		->appendName('Generator', 'Sublime Text 2, phpStorm, notepad++')
		->appendName('yandex-verification', '715d9bbdfc996f86')
		->setHttpEquiv('Cache-Control', 'no-store');

	// CSS setups
	// [JQUERY library]                             
	$view->headScript()->appendFile("/library/jquery/js/jquery-1.10.2.min.js");

	// [JQUERY UI library]
	$view->headLink()->appendStylesheet("/library/jquery/css/flick/jquery-ui-1.10.3.custom.min.css");
	$view->headScript()->appendFile("/library/jquery/js/jquery-ui-1.10.3.custom.min.js");

	// [BOOTSTRAP library]
	$view->headLink()->appendStylesheet("/library/bootstrap/css/bootstrap.min.css");
	$view->headScript()->appendFile("/library/bootstrap/js/bootstrap.min.js");

	// [SNOW FALL]
	$view->headScript()->appendFile("/library/JQuery-Snowfall/snowfall.min.jquery.js");
	
	$view->minifyHeadLink()->appendStylesheet("/library/normalize/normalize.css");

	// [FONT-AWESOME library]
	$view->headLink()->appendStylesheet("/library/font-awesome/css/font-awesome.min.css");
	//$view->headLink()->appendStylesheet("/library/bootstrap-glyphicons-gh-pages/css/bootstrap-glyphicons.css");
	
	// [GOOGLE FONTS]
	$view->headLink()->appendStylesheet("http://fonts.googleapis.com/css?family=Faster+One", "screen, print");
	//$view->headLink()->appendStylesheet("/library/google-fonts/Faster+One.css", "screen, print");

	// [COMMON CSS]
	$view->minifyHeadLink()->appendStylesheet('/css/items.css');
	$view->minifyHeadLink()->appendStylesheet('/css/forms.css');
	
	// [COMMON JS]
	$view->headScript()->appendFile("/js/app.js");

	//$view->MinifyHeadScript()->appendFile($view->baseUrl("js/app.js"));

	Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session());

	// ViewRenderer
	$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
			'ViewRenderer'
	);
	$viewRenderer->setView($view);

	return $view;
    }

    protected function _initLayoutHelper()
    {
	$this->bootstrap('frontController');
	$layout = Zend_Controller_Action_HelperBroker::addHelper(
			new App_Controller_Action_Helper_LayoutLoader());
    }

    public function _initViewHelpers()
    {
	//$layout = Zend_Layout::startMvc(array('layoutPath' => '../application/layouts'));
	$this->bootstrap('layout');
	$layout = $this->getResource('layout');
	$view = $layout->getView();
	$view->doctype('XHTML1_STRICT');
	//$view->doctype('HTML5');
	$view->addHelperPath('App/View/Helper', 'App_View_Helper');
    }

    public function _initActionHelpers()
    {
	Zend_Controller_Action_HelperBroker::addPrefix('App_Controller_Action_Helper');
    }

    public function _initAcl()
    {
	Zend_Loader::loadClass('Acl');
	Zend_Loader::loadClass('CheckAccess');
	Zend_Controller_Front::getInstance()->registerPlugin(new CheckAccess());
	return new Acl();
    }

    public function _initLogger()
    {
	$writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/application.log');
	$logger = new Zend_Log($writer);
	Zend_Registry::set('logger', $logger);
    }

    public function _initTranslate()
    {
	$config = $this->getOptions();
	$locales = $config['locales'];
	$locale = new Zend_Locale('auto');

	$lang = array_key_exists($locale->getLanguage(), $locales) ? $locale->getLanguage() : "ru";

	$zl = new Zend_Locale();
	if (isset($config['locales'][$lang])) {
	    $zl->setLocale($config['locales'][$lang]);
	} else {
	    $zl->setLocale('ru_RU');
	}
	Zend_Registry::set('Zend_Locale', $zl);

	$translate = new Zend_Translate(
		array(
	    'adapter' => 'gettext',
	    'content' => APPLICATION_PATH . '/languages/' . $lang . '.mo',
	    'locale' => $locale
		)
	);

	Zend_Registry::set('Zend_Translate', $translate);

	/* $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'staging');

	  $locales = $config->locales->toArray();
	  $locale = new Zend_Locale('auto');

	  $lang = array_key_exists($locale->getLanguage(), $locales) ? $locale->getLanguage() : $config->locales->key();
	 */
    }
    

}