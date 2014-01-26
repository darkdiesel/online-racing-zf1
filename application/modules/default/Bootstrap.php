<?php

/**
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 * When creating your own modules make sure that you are using the correct namespace 
 */
class Default_Bootstrap extends Zend_Application_Module_Bootstrap {

	protected function _initPlugins() {
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->registerPlugin(new App_Plugin_Module_Default($this));
	}

	public function _initRoutes() {
		$frontController = Zend_Controller_Front::getInstance();
		$router = $frontController->getRouter();

		$router->addRoute(
				'userId', new Zend_Controller_Router_Route_Regex('user/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'user',
			'action' => 'id',
			1 => 0
				), array(
			'user_id' => 1,
				), 'user/%d.html'
				)
		);

		$router->addRoute(
				'user', new Zend_Controller_Router_Route_Regex('user/(\d+)/(\w*)\.html', array(
			'module' => 'default',
			'controller' => 'user',
			1 => 0
				), array(
			'user_id' => 1,
			'action' => 2,
				), 'user/%d/%s.html'
				)
		);

		$router->addRoute(
				'userAll', new Zend_Controller_Router_Route_Regex('user/all/page/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'user',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "user/all/page/%s.html"
		));

		//post controller routers
		$router->addRoute(
				'postId', new Zend_Controller_Router_Route_Regex('post/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'post',
			'action' => 'id',
			1 => 0
				), array(
			'post_id' => 1,
				), "post/%d.html"
				)
		);

		$router->addRoute(
				'post', new Zend_Controller_Router_Route_Regex('post/(\d+)/(\w*)\.html', array(
			'module' => 'default',
			'controller' => 'post',
			1 => 0
				), array(
			'post_id' => 1,
			'action' => 2,
				), "post/%d/%s.html"
				)
		);

		$router->addRoute(
				'postAll', new Zend_Controller_Router_Route_Regex('post/all/page/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'post',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "post/all/page/%s.html"
		));

		$router->addRoute(
				'postByType', new Zend_Controller_Router_Route_Regex('post/by-type/(\d+)/page/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'post',
			'action' => 'by-type',
			1 => 0,
			2 => 1,
				), array(
			'post_type_id' => 1,
			'page' => 2,
				), "post/by-type/%d/page/%d.html"
		));


		$router->addRoute(
				'league', new Zend_Controller_Router_Route_Regex('league/(\d+)/(\w*)\.html', array(
			'module' => 'default',
			'controller' => 'league',
				), array(
			'league_id' => 1,
			'action' => 2,
				), "league/%d/%s.html"
				)
		);

		$router->addRoute(
				'leagueActionAddChamp', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship-add\.html', array(
			'module' => 'default',
			'controller' => 'championship',
			'action' => 'add',
				), array(
			'league_id' => 1,
				), "league/%d/championship-add.html"
				)
		);

		//league controller routers
		$router->addRoute(
				'leagueIdAll', new Zend_Controller_Router_Route_Regex('league/(\d+)/page/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'league',
			'action' => 'id',
			1 => 0,
			2 => 1,
				), array(
			'league_id' => 1,
			'page' => 2,
				), "league/%d/page/%d.html"
		));

		$router->addRoute(
				'leagueAll', new Zend_Controller_Router_Route_Regex('league/all/page/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'league',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "league/all/page/%s.html"
		));

		//championship controller routers
		$router->addRoute(
				'championship', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)/([^\/]+)\.html', array(
			'module' => 'default',
			'controller' => 'championship',
			1 => 0,
			2 => 0,
				), array(
			'league_id' => 1,
			'championship_id' => 2,
			'action' => 3,
				), "league/%d/championship/%d/%s.html"
				)
		);

		$router->addRoute(
				'championshipId', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'championship',
			'action' => 'id',
			1 => 0,
			2 => 0,
				), array(
			'league_id' => 1,
			'championship_id' => 2,
				), "league/%d/championship/%d.html"
				)
		);

		$router->addRoute(
				'championshipTeam', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)/team/(\d+)/([^\/]+)\.html', array(
			'module' => 'default',
			'controller' => 'championship',
			1 => 0,
			2 => 0,
			3 => 0
				), array(
			'league_id' => 1,
			'championship_id' => 2,
			'team_id' => 3,
			'action' => 4,
				), "league/%d/championship/%d/team/%d/%s.html"
				)
		);

		$router->addRoute(
				'championshipTeamDriver', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)/team/(\d+)/driver/(\d+)/?([^\/]+)?\.html', array(
			'module' => 'default',
			'controller' => 'championship',
			1 => 0,
			2 => 0,
			3 => 0,
				), array(
			'league_id' => 1,
			'championship_id' => 2,
			'team_id' => 3,
			'user_id' => 4,
			'action' => 5,
				), "league/%d/championship/%d/team/%d/driver/%d/%s.html"
				)
		);

		//RACE CONTROLLER ROUTERS
		$router->addRoute(
				'defaultChampionshipRaceAction', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)/race/(\w*)\.html', array(
			'module' => 'default',
			'controller' => 'race',
			'action' => 'add',
			1 => 0,
			2 => 0,
				), array(
			'league_id' => 1,
			'championship_id' => 2,
			'action' => 3,
				),
			"league/%d/championship/%d/race/%s.html"
				)
		);
		
		$router->addRoute(
				'defaultChampionshipRaceId', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)/race/(\d+)\.html', array(
			'module' => 'default',
			'controller' => 'race',
			'action' => 'id',
			1 => 0,
			2 => 0,
			3 => 0,
				), array(
			'league_id' => 1,
			'championship_id' => 2,
			'race_id' => 3,
				),
			"league/%d/championship/%d/race/%d.html"
				)
		);
		
		$router->addRoute(
				'defaultChampionshipRaceIdAction', new Zend_Controller_Router_Route_Regex('league/(\d+)/championship/(\d+)/race/(\d+)/(\w*)\.html', array(
			'module' => 'default',
			'controller' => 'race',
			1 => 0,
			2 => 0,
			3 => 0,
				), array(
			'league_id' => 1,
			'championship_id' => 2,
			'race_id' => 3,
			'action' => 4,
				),
			"league/%d/championship/%d/race/%d/%s.html"
				)
		);
		
		/*
		  $router->addRoute(
		  'championshipAll', new Zend_Controller_Router_Route('championship/all/:page', array(
		  'module' => 'default',
		  'controller' => 'championship',
		  'action' => 'all',
		  'page' => 1)
		  )); */

		
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
		$layout = $this->getResource('layout');
		$view = $layout->getView();

		$main_menu_pages = array(
			array(
				// Я обворачиваю текст в _(), чтобы потом вытянуть его парсером gettext'а
				'label' => _('Главная'),
				'module' => 'default',
				'controller' => 'index',
				'action' => 'index',
				'route' => 'default',
			),
			array(
				'label' => _('Лиги'),
				'title' => _('Лиги'),
				'module' => 'default',
				'controller' => 'league',
				'action' => 'all',
				'route' => 'leagueAll',
				'pages' => array(
					array(
						'label' => _('F1 ORL'),
						'title' => _('F1 ORL'),
						'module' => 'default',
						'controller' => 'league',
						'action' => 'id',
						'route' => 'leagueIdAll',
						'params' => array(
							'league_id' => '1'
						),
					),
					array(
						'label' => _('F1 ORS League'),
						'title' => _('F1 ORS League'),
						'module' => 'default',
						'controller' => 'league',
						'action' => 'id',
						'route' => 'leagueIdAll',
						'params' => array(
							'league_id' => '2'
						),
					),
					array(
						'label' => _('ORT League'),
						'title' => _('ORT League'),
						'module' => 'default',
						'controller' => 'league',
						'action' => 'id',
						'route' => 'leagueIdAll',
						'params' => array(
							'league_id' => '3'
						),
					),
					array(
						'label' => _('Все лиги'),
						'title' => _('Все лиги портала'),
						'module' => 'default',
						'controller' => 'league',
						'action' => 'all',
						'route' => 'leagueAll',
					)
				)
			),
			array(
				'label' => _('Гонщики'),
				'title' => _('Все гонщики нашего портала'),
				'module' => 'default',
				'controller' => 'user',
				'action' => 'all',
				'route' => 'userAll',
			),
			array(
				'label' => _('Новости'),
				'title' => _('Все статьи опубликованные на нашем портале'),
				'module' => 'default',
				'controller' => 'post',
				'action' => 'all',
				'route' => 'postAll',
			),
			array(
				'label' => _('Файлы'),
				'title' => _('Файлы'),
				'uri' => '#',
				'pages' => array(
					array(
						'label' => _('Игры и Моды'),
						'title' => _('Игры и Моды'),
						'controller' => 'post',
						'module' => 'default',
						'action' => 'by-type',
						'route' => 'postByType',
						'params' => array(
							'post_type_id' => '3'
						),
					),
					array(
						'label' => _('FTP'),
						'title' => _('FTP'),
						'uri' => 'http://85.112.55.36:8080/http/',
					),
				)
			),
			array(
				'label' => _('Блог'),
				'title' => _('Блог'),
				'uri' => 'http://onlineracingnet.blogspot.com/',
			),
			array(
				'label' => _('Форум'),
				'title' => _('Форум'),
				'uri' => 'http://f1orl.forum2x2.ru/',
			),
		);

		// Создаем новый контейнер на основе нашей структуры
		$main_menu_container = new Zend_Navigation($main_menu_pages);

		// Передаем контейнер в View
		$view->main_menu = $main_menu_container;

		//return $main_menu_container;
		/* $this->bootstrap('layout');
		  $view = $this->getResource('layout')->getView();

		  $config = new Zend_Config_Xml(APPLICATION_PATH . '/configs/navigation.xml' , 'nav');
		  $navigation = new Zend_Navigation($config);
		  $view->navigation($navigation); */
	}

}