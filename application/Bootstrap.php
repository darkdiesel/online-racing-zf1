<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    public function _initConfig() {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV, array('allowModifications' => true));
        $config->domain = $_SERVER['HTTP_HOST'];
        $config->setReadOnly();
        Zend_Registry::set('config', $config);
    }

    protected function _initNameSpace() {
        Zend_Loader_Autoloader::getInstance()->registerNamespace('App');
    }

    protected function _initDb() {
        try {
            $config = $this->getOptions();
            $db = Zend_Db::factory($config['resources']['db']['adapter'], $config['resources']['db']['params']);
            Zend_Db_Table::setDefaultAdapter($db);

            /*$registry = Zend_Registry::getInstance();
            $registry->configuration = $config;
            $registry->dbAdapter = $db;
            $registry->session = new Zend_Session_Namespace();*/
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        Zend_Registry::set('db', $db);
        return $db;
    }

    protected function _initDoctype() {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
        //$view->doctype('HTML5');
    }

    // Initialisation Authorisation
    public function _initAuth() {
        //Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session());
    }

    protected function _initView() {
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

        // CSS setups
        // [BOOTSTRAP CSS]
        $view->headLink()->appendStylesheet($view->baseUrl("css/bootstrap.min.css"));
        // [JQUERY UI CSS]
        $view->headLink()->appendStylesheet($view->baseUrl("css/jquery-ui-1.10.2.custom.min.css"));        
        // [CHAT CSS]
        if ($view->ls_chat_block) {
            $view->headLink()->appendStylesheet($view->baseUrl("css/chat.css"));
        }
        // [CSS Minify]
        $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/style.css'));
        $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/user_toolbar.css'));
        $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/forms.css'));
        $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/articles.css'));
        $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/items.css'));
        $view->minifyHeadLink()->appendStylesheet($view->baseUrl('css/user.css'));
        // [COMMON CSS]
        //$view->headLink()->appendStylesheet($view->baseUrl("css/style.css"));
        //$view->headLink()->appendStylesheet($view->baseUrl("css/user_toolbar.css"));
        //$view->headLink()->appendStylesheet($view->baseUrl("css/forms.css"));
        //$view->headLink()->appendStylesheet($view->baseUrl("css/articles.css"));
        //$view->headLink()->appendStylesheet($view->baseUrl("css/items.css"));
        //$view->headLink()->appendStylesheet($view->baseUrl("css/user.css"));
        // [GOOGLE FONTS]
        $view->headLink()->appendStylesheet("http://fonts.googleapis.com/css?family=Faster+One", "screen, print");

        // [JQUERY JS]
        $view->headScript()->appendFile($view->baseUrl("js/jquery-1.9.1.min.js"));
        // [JQUERY UI JS]
        $view->headScript()->appendFile($view->baseUrl("js/jquery-ui-1.10.2.custom.min.js"));
        // [BOOTSTRAP JS]
        $view->headScript()->appendFile($view->baseUrl("js/bootstrap.min.js"));
        // [COMMON JS]
        $view->headScript()->appendFile($view->baseUrl("js/app.js"));

        //$view->MinifyHeadScript()->appendFile($view->baseUrl("js/app.js"));
        
        // Share block script
        if ($view->lo_share_block) {
            $view->headScript()->appendFile($view->baseUrl("js/share.js"));
        }

        // Script for main menu
        // Script for count down block
        if ($view->ls_next_event_block) {
            $view->headScript()->appendFile($view->baseUrl("js/jquery.countdown.min.js"));
            $view->headScript()->appendFile($view->baseUrl("js/jquery.countdown-ru.js"));
            $view->headLink()->appendStylesheet($view->baseUrl("css/jquery.countdown.css"));
        }

        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session());

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
    
    public function _initViewHelpers() {
    	//$layout = Zend_Layout::startMvc(array('layoutPath' => '../application/layouts'));
    	$this->bootstrap('layout');
    	$view = $this->getResource('layout')->getView();
    	$view->addHelperPath('App/View/Helper', 'App_View_Helper');
    }
    
    public function _initActionHelpers() {
    	Zend_Controller_Action_HelperBroker::addPrefix('App_Controller_Action_Helper');
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

    public function _initRoutes() {
        $frontController = Zend_Controller_Front::getInstance();
        $router = $frontController->getRouter();

        $router->addRoute(
                'user', new Zend_Controller_Router_Route_Regex('user/(\w*)/(\d+)\.html', array(
            'module' => 'default',
            'controller' => 'user',
            2 => 0
                ), array(
            'action' => 1,
            'id' => 2
                ), 'user/%s/%s.html'
                )
        );

        $router->addRoute(
                'userAll', new Zend_Controller_Router_Route('user/all/:page', array(
            'module' => 'default',
            'controller' => 'user',
            'action' => 'all',
            'page' => 1)
        ));
        //article controller routers
        $router->addRoute(
                'article', new Zend_Controller_Router_Route_Regex('article/(\w*)/(\d+)\.html', array(
            'module' => 'default',
            'controller' => 'article',
            2 => 0
                ), array(
            'action' => 1,
            'id' => 2
                ), "article/%s/%s.html"
                )
        );


        $router->addRoute(
                'articleAll', new Zend_Controller_Router_Route('article/all/:page', array(
            'module' => 'default',
            'controller' => 'article',
            'action' => 'all',
            'page' => 1)
        ));

        $router->addRoute(
                'articleAllByType', new Zend_Controller_Router_Route('article/all-by-type/:article_type_id/:page', array(
            'module' => 'default',
            'controller' => 'article',
            'action' => 'all-by-type',
            'article_type_id' => 0,
            'page' => 1)
        ));

        //admin controller routers
        $router->addRoute(
                'adminArticleAll', new Zend_Controller_Router_Route('admin/articles/:page', array(
            'module' => 'default',
            'controller' => 'admin',
            'action' => 'articles',
            'page' => 1)
        ));

        $router->addRoute(
                'adminUserAll', new Zend_Controller_Router_Route('admin/users/:page', array(
            'module' => 'default',
            'controller' => 'admin',
            'action' => 'users',
            'page' => 1)
        ));

        $router->addRoute(
                'adminLeagueAll', new Zend_Controller_Router_Route('admin/leagues/:page', array(
            'module' => 'default',
            'controller' => 'admin',
            'action' => 'leagues',
            'page' => 1)
        ));
        //article-type controller routers
        $router->addRoute(
                'articleTypeId', new Zend_Controller_Router_Route('article-type/:action/:id', array(
            'module' => 'default',
            'controller' => 'article-type',
            'id' => 0)
        ));

        //content type controller routers
        $router->addRoute(
                'contentTypeId', new Zend_Controller_Router_Route('content-type/:action/:id', array(
            'module' => 'default',
            'controller' => 'content-type',
            'id' => 0)
        ));

        //league controller routers
        $router->addRoute(
                'leagueId', new Zend_Controller_Router_Route('league/id/:id/:page', array(
            'module' => 'default',
            'controller' => 'league',
            'action' => 'id',
            'id' => 0,
            'page' => 1)
        ));

        $router->addRoute(
                'league', new Zend_Controller_Router_Route_Regex('league/(\w*)/(\d+)\.html', array(
            'module' => 'default',
            'controller' => 'league',
                ), array(
            'action' => 1,
            'id' => 2
                ), "league/%s/%s.html"
                )
        );

        $router->addRoute(
                'leagueAll', new Zend_Controller_Router_Route('league/all/:page', array(
            'module' => 'default',
            'controller' => 'league',
            'action' => 'all',
            'page' => 1)
        ));


        //team controller routers
        $router->addRoute(
                'team', new Zend_Controller_Router_Route('team/:action/:id', array(
            'module' => 'default',
            'controller' => 'team',
            'id' => 0)
        ));

        $router->addRoute(
                'teamAll', new Zend_Controller_Router_Route('team/all/:page', array(
            'module' => 'default',
            'controller' => 'team',
            'action' => 'all',
            'page' => 1)
        ));
        //championship controller routers
        $router->addRoute(
                'championship', new Zend_Controller_Router_Route_Regex('championship/(\w*)/(\d+)\.html', array(
            'module' => 'default',
            'controller' => 'championship',
            2 => 0,
                ), array(
            'action' => 1,
            'championship_id' => 2
                ), "championship/%s/%s.html"
                )
        );

        $router->addRoute(
                'championshipTeam', new Zend_Controller_Router_Route_Regex('championship/(\d+)/([^\/]+)/(\d+)\.html', array(
            'module' => 'default',
            'controller' => 'championship',
            1 => 0,
            3 => 0
                ), array(
            'championship_id' => 1,
            'action' => 2,
            'team_id' => 3
                ), "championship/%s/%s/%s.html"
                )
        );

        $router->addRoute(
                'championshipTeamAction', new Zend_Controller_Router_Route_Regex('championship/(\d+)/(\w*)/(\d+)/([^\/]+)\.html', array(
            'module' => 'default',
            'controller' => 'championship',
            1 => 0,
            2 => 'team',
            3 => 0,
                ), array(
            'championship_id' => 1,
            'team_id' => 3,
            'action' => 4,
                ), "championship/%s/%s/%s/%s.html"
                )
        );

        $router->addRoute(
                'championshipTeamDriverId', new Zend_Controller_Router_Route_Regex('championship/(\d+)/(\w*)/(\d+)/?([^\/]+)?/(\d+)\.html', array(
            'module' => 'default',
            'controller' => 'championship',
            1 => 0,
            2 => 'team',
            3 => 0,
                ), array(
            'championship_id' => 1,
            'team_id' => 3,
            'action' => 4,
            'user_id' => 5,
                ), "championship/%s/%s/%s/%s/%s.html"
                )
        );

        $router->addRoute(
                'championshipTeamDefault', new Zend_Controller_Router_Route_Regex('championship/(\d+)/?([^\/]+)?\.html', array(
            'module' => 'default',
            'controller' => 'championship',
            1 => 0,
                ), array(
            'championship_id' => 1,
            'action' => 2,
                ), "championship/%s/%s.html"
                )
        );

        $router->addRoute(
                'championshipAll', new Zend_Controller_Router_Route('championship/all/:page', array(
            'module' => 'default',
            'controller' => 'championship',
            'action' => 'all',
            'page' => 1)
        ));
        //event controller routers
        $router->addRoute(
                'event', new Zend_Controller_Router_Route('event/:action/:id', array(
            'module' => 'default',
            'controller' => 'event',
            'id' => 0)
        ));

        $router->addRoute(
                'eventAll', new Zend_Controller_Router_Route('event/all/:page', array(
            'module' => 'default',
            'controller' => 'event',
            'action' => 'all',
            'page' => 1)
        ));
        //country controller routers
        $router->addRoute(
                'country', new Zend_Controller_Router_Route('country/:action/:id', array(
            'module' => 'default',
            'controller' => 'country',
            'id' => 0)
        ));

        $router->addRoute(
                'countryAll', new Zend_Controller_Router_Route('country/all/:page', array(
            'module' => 'default',
            'controller' => 'country',
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

    public function _initNavigation() {
        $this->bootstrap('layout');
        $view = $this->getResource('layout')->getView();

        $main_menu_pages = array(
            array(
                // Я обворачиваю текст в _(), чтобы потом вытянуть его парсером gettext'а
                'label' => _('Главная'),
                'controller' => 'index',
                'action' => 'index',
                'route' => 'default',
            ),
            array(
                'label' => _('Лиги'),
                'title' => _('Лиги'),
                'controller' => 'league',
                'action' => 'all',
                'route' => 'leagueAll',
                'pages' => array(
                    array(
                        'label' => _('F1 Online-Racing League'),
                        'title' => _('F1 Online-Racing League'),
                        'controller' => 'league',
                        'action' => 'id',
                        'route' => 'leagueId',
                        'params' => array(
                            'id' => '1'
                        ),
                    ),
                    array(
                        'label' => _('Все лиги'),
                        'title' => _('Все лиги портала'),
                        'controller' => 'league',
                        'action' => 'all',
                        'route' => 'leagueAll',
                    )
                )
            ),
            array(
                'label' => _('Гонщики'),
                'title' => _('Все гонщики нашего портала'),
                'controller' => 'user',
                'action' => 'all',
                'route' => 'userAll',
            ),
            array(
                'label' => _('Новости'),
                'title' => _('Все статьи опубликованные на нашем портале'),
                'controller' => 'article',
                'action' => 'all',
                'route' => 'articleAll',
            ),
            array(
                'label' => _('Файлы'),
                'title' => _('Файлы'),
                'uri' => '#',
                'pages' => array(
                    array(
                        'label' => _('Игры и Моды'),
                        'title' => _('Игры и Моды'),
                        'controller' => 'article',
                        'action' => 'all-by-type',
                        'route' => 'articleAllByType',
                        'params' => array(
                            'article_type_id' => '3'
                        ),
                    ),
                )
            ),
            array(
                'label' => _('Форум'),
                'title' => _('Форум'),
                'uri' => 'http://f1orl.forum2x2.ru/',
            ),
        );

        $breadcrumbs_pages = array(
            array(
                // Я обворачиваю текст в _(), чтобы потом вытянуть его парсером gettext'а
                'label' => _('Главная'),
                'controller' => 'index',
                'action' => 'index',
                'route' => 'default',
                'pages' => array(
                    array(
                        'label' => _('Лиги'),
                        'title' => _('Лиги'),
                        'controller' => 'league',
                        'action' => 'all',
                        'route' => 'leagueAll',
                        'pages' => array(
                            array(
                                'label' => _('F1 Online-Racing League'),
                                'title' => _('F1 Online-Racing League'),
                                'controller' => 'league',
                                'action' => 'id',
                                'route' => 'leagueId',
                                'params' => array(
                                    'id' => '1'
                                ),
                            ),
                            array(
                                'label' => _('Все лиги'),
                                'title' => _('Все лиги'),
                                'controller' => 'league',
                                'action' => 'all',
                                'route' => 'leagueAll',
                            )
                        )
                    ),
                    array(
                        'controller' => 'user',
                        'action' => 'all',
                        'label' => _('Гонщики'),
                        'title' => _('Гонщики'),
                        'route' => 'userAll',
                        'pages' => array(
                            array(
                                'label' => _('Пилот'),
                                'title' => _('Пилот'),
                                'controller' => 'user',
                                'action' => 'id',
                                'route' => 'user',
                                'params' => array(
                                )
                            )
                        )
                    ),
                    array(
                        'label' => _('Новости'),
                        'title' => _('Новости'),
                        'controller' => 'article',
                        'action' => 'all',
                        'route' => 'articleAll',
                        'pages' => array(
                            array(
                                'label' => _('Статья'),
                                'title' => _('Статья'),
                                'controller' => 'article',
                                'action' => 'id',
                                'route' => 'article',
                                'params' => array()
                            )
                        )
                    ),
                    array(
                        'label' => _('Файлы'),
                        'title' => _('Файлы'),
                        'uri' => '',
                        'pages' => array(
                            array(
                                'label' => _('Игры и Моды'),
                                'controller' => 'article',
                                'action' => 'all-by-type',
                                'route' => 'articleAllByType',
                                'params' => array(
                                    'article_type_id' => '3'
                                ),
                                'pages' => array(
                                    array(
                                        'label' => _('Игра'),
                                        'title' => _('Игра'),
                                        'controller' => 'article',
                                        'action' => 'id',
                                        'route' => 'article',
                                        'params' => array()
                                    )
                                )
                            ),
                        )
                    ),
                    array(
                        'label' => _('Форум'),
                        'title' => _('Форум'),
                        'uri' => 'http://f1orl.forum2x2.ru/',
                    ),
                    array(
                        'label' => _('Админ. панель'),
                        'title' => _('Панель администратора'),
                        'controller' => 'admin',
                        'action' => 'index',
                        'route' => 'default',
                        'pages' => array(
                        )
                    )
                )
            ),
        );

        // Создаем новый контейнер на основе нашей структуры
        $main_menu_container = new Zend_Navigation($main_menu_pages);
        $breadcrumb_container = new Zend_Navigation($breadcrumbs_pages);
        // Передаем контейнер в View
        $view->main_menu = $main_menu_container;
        $view->breadcrumb = $breadcrumb_container;

        //return $main_menu_container;

        /* $this->bootstrap('layout');
          $view = $this->getResource('layout')->getView();

          $config = new Zend_Config_Xml(APPLICATION_PATH . '/configs/navigation.xml' , 'nav');
          $navigation = new Zend_Navigation($config);
          $view->navigation($navigation); */
    }

}