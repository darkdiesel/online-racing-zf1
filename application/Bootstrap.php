<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    public function _initConfig()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH
        . '/configs/application.ini', APPLICATION_ENV, array('allowModifications' => true));
        $config->domain = $_SERVER['HTTP_HOST'];
        $config->setReadOnly();
        Zend_Registry::set('config', $config);
    }

    protected function _initNameSpace()
    {
        Zend_Loader_Autoloader::getInstance()->registerNamespace('App');
        Zend_Loader_Autoloader::getInstance()->registerNamespace('Bootstrap');
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

        if (Zend_Auth::getInstance()->hasIdentity()) {
            if (isset($_COOKIE['RememberMe'])) {
                $rememberMe = $_COOKIE['RememberMe'];
            } else {
                $rememberMe = 0;
            }

            if ($rememberMe) {
                Zend_Session::rememberMe(60 * 60 * 120);
                setcookie('RememberMe', 1, 60 * 60 * 120, '/');
            }
        }
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

        //Register variables for views
        $frontController->registerPlugin(new App_Controller_Plugin_ViewSetup());

        //$frontController->registerPlugin(new App_Plugin_SessionTrack());
    }

    public function _initActionHelpers()
    {
        Zend_Controller_Action_HelperBroker::addPrefix('App_Controller_Action_Helper');
    }

    protected function _initView()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();

        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();

        // Init DocType
        $view->doctype('XHTML1_STRICT');

        // Init View Helpers
        $view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
        $view->addHelperPath('App/View/Helper', 'App_View_Helper');
        $view->addHelperPath('Bootstrap/View/Helper', 'Bootstrap_View_Helper');

        // setting the site in the title; possibly in the layout script:
        $view->headTitle('Online-Racing.Net');
        // setting a separator string for segments:
        $view->headTitle()->setSeparator(' | ');

        // Init head meta
        $view->headMeta()
            ->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
            ->setHttpEquiv('X-UA-Compatible', 'IE=edge')
            ->appendHttpEquiv('Content-Language', 'en-US')
            ->appendHttpEquiv('Content-Language', 'ru')
            ->appendName(
                'description',
                'Sim-Racing Portal with unique functionality that provides opportunities as a social network.'
            )
            ->appendName(
                'keywords', 'F1, F-1, Online F1, F1 News, Формула-1, Formula One, rFactor, Online-Racing, Онлайн гонки, RFT, Sim Racing, Race,
                               Гонки, Новости Формулы 1, сим-рейсинг, championship, formula1 скачать, русификатор, rFactor lite, ORM '
            )
            ->appendName('subject', 'Sim-Racing')
            ->appendName('title', 'Online-Racing')
            ->appendName('revisit', '5 days')
            ->appendName('resource-type', 'document')
            ->appendName('Copyright', 'Igor Peshkov. Copyright 2012-2014')
            ->appendName('Author', 'Igor Peshkov. Copyright 2012-2014')
            ->appendName('reply-to', 'Igor.Peshkov@gmail.com')
            ->appendName('Generator', 'NetBeans, notepad++, PHPStorm')
            ->appendName('yandex-verification', '715d9bbdfc996f86')
            ->appendName('viewport', 'width=device-width, initial-scale=1')
            ->setHttpEquiv('Cache-Control', 'no-store');

        // setup JQuery
        $view->jQuery()
            ->enable()
            ->uiEnable()
            ->setVersion('1.11.1')
            ->setUiVersion('1.11.0')
            //->setLocalPath('/library/jquery/js/jquery.min.js')
            //->setUiLocalPath('/library/jquery/js/jquery-ui.min.js')
            ->addStylesheet('/library/jquery/css/jquery-ui.min.css');

        $viewRenderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
    }

    /*
     * Helper load layout for modules
     */
    protected function _initLayoutHelper()
    {
        $this->bootstrap('frontController');
        $layout = Zend_Controller_Action_HelperBroker::addHelper(
            new App_Controller_Action_Helper_LayoutLoader()
        );
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
                 'locale'  => $locale
            )
        );

        Zend_Registry::set('Zend_Translate', $translate);

        /* $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'staging');

          $locales = $config->locales->toArray();
          $locale = new Zend_Locale('auto');

          $lang = array_key_exists($locale->getLanguage(), $locales) ? $locale->getLanguage() : $config->locales->key();
         */
    }

    public function _initCache()
    {
        $this->bootstrap('cachemanager');
        $manager = $this->getResource('cachemanager');

        //кеш метаданных
        //Zend_Db_Table_Abstract::setDefaultMetadataCache($manager->getCache('long'));
        //время кеширования для кеша обновляемой рывками инфы
        //$manager->getCache('up')->setLifetime($time);
        $cache = Zend_Cache::factory(
            'Core', 'File', array(
                                 'lifetime' => 3600 * 24, //cache is cleaned once a day
                                 'automatic_serialization' => true
                            ), array('cache_dir' => APPLICATION_PATH . '/../cache/')
        );
        Zend_Db_Table_Abstract::setDefaultMetadataCache(
            $cache
        ); //cache database table schemata metadata for faster SQL queries
        Zend_Registry::set('Cache', $cache);

        Zend_Registry::set('cache', $cache);
    }

}
