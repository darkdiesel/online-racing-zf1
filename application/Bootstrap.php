<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    public function _initConfig() {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV, array('allowModifications' => true));
        $config->domain = $_SERVER['HTTP_HOST'];
        $config->setReadOnly();
        Zend_Registry::set('config', $config);
    }

    protected function _initDb() {
        try {
            $config = $this->getOptions();
            $db = Zend_Db::factory($config['resources']['db']['adapter'], $config['resources']['db']['params']);
            Zend_Db_Table::setDefaultAdapter($db);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
        Zend_Registry::set('db', $db);
        return $db;
    }

    protected function _initDoctype() {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        //$view->doctype('XHTML1_STRICT');
        $view->doctype('HTML5');
    }

    protected function _initView() {
        $view = new Zend_View();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        /* [MAIN SITE TITLE SETTINGS] */
        $view->headTitle('Online-Racing.net')
                ->setSeparator(' - '); // setting a separator string for segments

        /* [HEAD META SETTINGS] */
        $view->headMeta()
                ->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
                ->setHttpEquiv('X-UA-Compatible', 'IE=edge')
                ->setHttpEquiv('X-UA-Compatible', 'IE=EmulateIE9')
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

        /* [BLOCK DISPLAY SETTINGS] */

        /* [LAYOUT] */
        $view->lo_share_block = true; // share block
        $view->lo_back_to_top = true; // back to top block

        /* [LEFT SIDEBAR] */
        $view->ls_count_down_block = false; // count down block
        $view->ls_chat_block = true; // chat block
        $view->ls_user_map_block = true; // user map block
        $view->ls_search_block = false; // search block

        /* [HEADER] */
        $view->hd_hot_lap_block = true; // hot lap block
        $view->hd_moving_text_block = true; // moving text block

        /* [FOOTER] */
        $view->ft_menu = true; // footer menu
        $view->ft_socialNetworks_block = true; // footer social networks block
        $view->ft_user_counters_block = true; // user counters block

        /* [FOOTER COUNTER] */
        $view->uc_live_internet_block = true; // liveinternet user counter block
        $view->uc_mail_ru_block = true; // mail.ru user counter block
        $view->uc_rambler100_block = true; // rambler 100 user counter block
        $view->uc_yandex_block = true; // yandex user counter block

        /* [CSS SETTINGS] */
        /* [BOOTSTRAP CSS] */
        $view->headLink()->appendStylesheet($view->baseUrl("css/bootstrap.css"));
        /* [JQUERY UI CSS] */
        $view->headLink()->appendStylesheet($view->baseUrl("css/jquery-ui-1.9.0.custom.min.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/style.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/main_menu.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/user_toolbar.css"));

        /* [CHAT CSS] */
        if ($view->ls_chat_block) {
            $view->headLink()->appendStylesheet($view->baseUrl("css/chat.css"));
        }

        // master menu
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session('online-racing'));
        $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
        $mapper = new Application_Model_UserMapper();

        if (Zend_Auth::getInstance()->hasIdentity()) {
            $logined_user_id = $mapper->getUserRole($storage_data->id);
            if ($logined_user_id == 1) {
                $view->headLink()->appendStylesheet($view->baseUrl("css/master_toolbar.css"));
                $view->showMasterPanel = 1;
            } elseif ($logined_user_id == 2) {
                $view->headLink()->appendStylesheet($view->baseUrl("css/master_toolbar.css"));
                $view->showMasterPanel = 2;
            } else {
                $view->showMasterPanel = 0;
            }
        }

        /* [GOOGLE FONTS] */
        $view->headLink()->appendStylesheet("http://fonts.googleapis.com/css?family=PT+Serif&subset=latin,cyrillic", "screen, print");
        $view->headLink()->appendStylesheet("http://fonts.googleapis.com/css?family=Press+Start+2P&subset=latin,cyrillic", "screen, print");

        /* [JQUERY JS] */
        $view->headScript()->appendFile($view->baseUrl("js/jquery-1.8.2.min.js"));
        /* [JQUERY UI JS] */
        $view->headScript()->appendFile($view->baseUrl("js/jquery-ui-1.9.0.custom.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/bootstrap.min.js"));

        // Share block script
        if ($view->lo_share_block) {
            $view->headScript()->appendFile($view->baseUrl("js/share.js"));
        }

        // Script for main menu
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lavalamp.my.js"));

        // Script for count down block
        if ($view->ls_count_down_block) {
            $view->headScript()->appendFile($view->baseUrl("js/jquery.lwtCountdown.js"));
        }

        // All Common scripts
        $view->headScript()->appendFile($view->baseUrl("js/my_js.js"));

        if ((Zend_Auth::getInstance()->hasIdentity()) && ($view->ls_chat_block)) {
            //chat script
            $view->headScript()->appendFile($view->baseUrl("js/chat.js"));
        }

        // ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
                        'ViewRenderer'
        );
        $viewRenderer->setView($view);

        return $view;
    }

    // Initialisation Authorisation
    public function _initAuth() {
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session('online-racing'));
    }

    public function _initAcl() {
        Zend_Loader::loadClass('Acl');
        Zend_Loader::loadClass('CheckAccess');
        Zend_Controller_Front::getInstance()->registerPlugin(new CheckAccess());
        return new Acl();
    }

    public function _initLogger() {
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/application.log');
        $logger = new Zend_Log($writer);
        Zend_Registry::set('logger', $logger);
    }

    public function _initTranslate() {
        $config = $this->getOptions();
        $locales = $config['locales'];
        $locale = new Zend_Locale('auto');

        $lang = array_key_exists($locale->getLanguage(), $locales) ? $locale->getLanguage() : "ru";

        $zl = new Zend_Locale();
        if (isset($config['locales'][$lang])) {
            $zl->setLocale($config['locales'][$lang]);
        } else {
            $zf->setLocale('ru_RU');
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

    public function _initRoutes() {
        $frontController = Zend_Controller_Front::getInstance();
        $router = $frontController->getRouter();

        $router->addRoute(
                'userinfo', new Zend_Controller_Router_Route('user/info/:id',
                        array(
                            'controller' => 'user',
                            'action' => 'info',
                            'id' => 0)
        ));

        $router->addRoute(
                'allusers', new Zend_Controller_Router_Route('user/all/:page',
                        array(
                            'module' => 'default',
                            'controller' => 'user',
                            'action' => 'all',
                            'page' => 1)
        ));

        $router->addRoute(
                'articleview', new Zend_Controller_Router_Route('article/view/:id',
                        array(
                            'module' => 'default',
                            'controller' => 'article',
                            'action' => 'view',
                            'id' => 0)
        ));
        
        $router->addRoute(
                'allarticles', new Zend_Controller_Router_Route('article/all/:page',
                        array(
                            'module' => 'default',
                            'controller' => 'article',
                            'action' => 'all',
                            'page' => 1)
        ));

        /*
          Zend_Loader::loadClass('App_Controller_Plugin_LangSelector');
          Zend_Controller_Front::getInstance()->registerPlugin(new App_Controller_Plugin_LangSelector());

          $frontController = Zend_Controller_Front::getInstance();
          $router = $frontController->getRouter();
          $router->removeDefaultRoutes();

          $router->addRoute(
          'default', new Zend_Controller_Router_Route('/',
          array(
          'lang' => 'ru',
          'module' => 'default',
          'controller' => 'index',
          'action' => 'index'
          )
          )
          );

          $router->addRoute(
          'lang', new Zend_Controller_Router_Route('/:lang',
          array(
          'lang' => 'ru',
          'module' => 'default',
          'controller' => 'index',
          'action' => 'index'
          )
          )
          );

          $router->addRoute(
          'controller', new Zend_Controller_Router_Route('/:lang/:controler',
          array(
          'lang' => ':lang',
          'module' => 'default',
          'action' => 'index'
          )
          )
          );

          $router->addRoute(
          'langcontrolleraction', new Zend_Controller_Router_Route('/:lang/:controller/:action',
          array(
          'lang' => ':lang',
          'module' => 'default'
          )
          )
          );

          $router->addRoute(
          'langmodcontrolleraction', new Zend_Controller_Router_Route('/:lang/:module',
          array('lang' => ':lang'
          )
          )
          );
          $router->addRoute(
          'langmodcontrolleraction', new Zend_Controller_Router_Route('/:lang/:module/:controller/:action',
          array('lang' => ':lang')
          )
          ); */
    }

}