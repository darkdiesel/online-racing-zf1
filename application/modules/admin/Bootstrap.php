<?php

/**
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 * When creating your own modules make sure that you are using the correct namespace 
 */
class Admin_Bootstrap extends Zend_Application_Module_Bootstrap {

	protected function _initPlugins() {
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->registerPlugin(new App_Plugin_Module_Admin($this));
	}

	public function _initRoutes() {
		$frontController = Zend_Controller_Front::getInstance();
		$router = $frontController->getRouter();

		//USER CONTROLLER ROUTERS
		$router->addRoute(
				'user_id', new Zend_Controller_Router_Route_Regex('admin/user/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'user',
			'action' => 'id',
			1 => 0
				), array(
			'user_id' => 1,
				), "admin/user/%d.html"
				)
		);

		$router->addRoute(
				'user_action', new Zend_Controller_Router_Route_Regex('admin/user/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'user',
			1 => 0
				), array(
			'user_id' => 1,
			'action' => 2,
				), "admin/user/%d/%s.html"
				)
		);

		$router->addRoute(
				'adminUserAll', new Zend_Controller_Router_Route_Regex('admin/user/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'user',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/user/all/page/%s.html"
		));

		//RESOURCE CONTROLLER ROUTERS
		$router->addRoute(
				'adminResourceId', new Zend_Controller_Router_Route_Regex('admin/resource/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'resource',
			'action' => 'id',
			1 => 0
				), array(
			'resource_id' => 1,
				), "admin/resource/%d.html"
				)
		);

		$router->addRoute(
				'adminResourceAction', new Zend_Controller_Router_Route_Regex('admin/resource/(\d+)/([^\/]+)\.html', array(
			'module' => 'admin',
			'controller' => 'resource',
			1 => 0
				), array(
			'resource_id' => 1,
			'action' => 2,
				), "admin/resource/%d/%s.html"
				)
		);

		$router->addRoute(
				'adminResourceAll', new Zend_Controller_Router_Route_Regex('admin/resource/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'resource',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/resource/all/page/%s.html"
		));

		//ROLE CONTROLLER ROUTERS
		$router->addRoute(
				'adminRoleId', new Zend_Controller_Router_Route_Regex('admin/role/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'role',
			'action' => 'id',
			1 => 0
				), array(
			'role_id' => 1,
				), "admin/role/%d.html"
				)
		);

		$router->addRoute(
				'adminRoleAction', new Zend_Controller_Router_Route_Regex('admin/role/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'role',
			1 => 0
				), array(
			'role_id' => 1,
			'action' => 2,
				), "admin/role/%d/%s.html"
				)
		);

		$router->addRoute(
				'adminRoleAll', new Zend_Controller_Router_Route_Regex('admin/role/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'role',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/role/all/page/%s.html"
		));

		//RESOURCE CONTROLLER ROUTERS
		$router->addRoute(
				'adminResourceAccessId', new Zend_Controller_Router_Route_Regex('admin/resource-access/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'resource-access',
			'action' => 'id',
			1 => 0), array(
			'resource_access_id' => 1,
				), "admin/resource-access/%d.html"
		));

		$router->addRoute(
				'adminResourceAccessAction', new Zend_Controller_Router_Route_Regex('admin/resource-access/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'resource-access',
			'action' => 'id',
			1 => 0), array(
			'resource_access_id' => 1,
			'action' => 2,
				), "admin/resource-access/%d/%s.html"
		));

		$router->addRoute(
				'adminResourceAccessAll', new Zend_Controller_Router_Route_Regex('admin/resource-access/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'resource-access',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/resource-access/all/page/%s.html"
		));

		//PRIVILEGE CONTROLLER ROUTERS
		$router->addRoute(
				'adminPrivilegeId', new Zend_Controller_Router_Route_Regex('admin/privilege/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'privilege',
			'action' => 'id',
			1 => 0), array(
			'privilege_id' => 1,
				), "admin/privilege/%d.html"
		));

		$router->addRoute(
				'adminPrivilegeAction', new Zend_Controller_Router_Route_Regex('admin/privilege/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'privilege',
			'action' => 'id',
			1 => 0), array(
			'privilege_id' => 1,
			'action' => 2,
				), "admin/privilege/%d/%s.html"
		));

		$router->addRoute(
				'adminPrivilegeAll', new Zend_Controller_Router_Route_Regex('admin/privilege/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'privilege',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/privilege/all/page/%s.html"
		));
		
		//POST CONTROLLER ROUTERS
		$router->addRoute(
				'adminPostId', new Zend_Controller_Router_Route_Regex('admin/post/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'post',
			'action' => 'id',
			1 => 0), array(
			'post_id' => 1,
				), "admin/post/%d.html"
		));

		$router->addRoute(
				'adminPostAction', new Zend_Controller_Router_Route_Regex('admin/post/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'post',
			'action' => 'id',
			1 => 0), array(
			'post_id' => 1,
			'action' => 2,
				), "admin/post/%d/%s.html"
		));

		$router->addRoute(
				'adminPostAll', new Zend_Controller_Router_Route_Regex('admin/post/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'post',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/post/all/page/%s.html"
		));
		
		//LEAGUE CONTROLLER ROUTERS
		$router->addRoute(
				'adminLeagueId', new Zend_Controller_Router_Route_Regex('admin/league/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'league',
			'action' => 'id',
			1 => 0), array(
			'league_id' => 1,
				), "admin/league/%d.html"
		));

		$router->addRoute(
				'adminLeagueAction', new Zend_Controller_Router_Route_Regex('admin/league/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'league',
			'action' => 'id',
			1 => 0), array(
			'league_id' => 1,
			'action' => 2,
				), "admin/league/%d/%s.html"
		));

		$router->addRoute(
				'adminLeagueAll', new Zend_Controller_Router_Route_Regex('admin/league/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'league',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/league/all/page/%s.html"
		));
		
		//CHAMPIONSHIP CONTROLLER ROUTERS
		$router->addRoute(
				'adminChampionshipId', new Zend_Controller_Router_Route_Regex('admin/championship/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'championship',
			'action' => 'id',
			1 => 0), array(
			'championship_id' => 1,
				), "admin/championship/%d.html"
		));

		$router->addRoute(
				'adminChampionshipAction', new Zend_Controller_Router_Route_Regex('admin/championship/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'championship',
			'action' => 'id',
			1 => 0), array(
			'championship_id' => 1,
			'action' => 2,
				), "admin/championship/%d/%s.html"
		));

		$router->addRoute(
				'adminChampionshipAll', new Zend_Controller_Router_Route_Regex('admin/championship/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'championship',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/championship/all/page/%s.html"
		));

		//POST TYPE CONTROLLER ROUTERS
		$router->addRoute(
				'adminPostTypeId', new Zend_Controller_Router_Route_Regex('admin/post-type/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'post-type',
			'action' => 'id',
			1 => 0), array(
			'post_type_id' => 1,
				), "admin/post-type/%d.html"
		));

		$router->addRoute(
				'adminPostTypeAction', new Zend_Controller_Router_Route_Regex('admin/post-type/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'post-type',
			'action' => 'id',
			1 => 0), array(
			'post_type_id' => 1,
			'action' => 2,
				), "admin/post-type/%d/%s.html"
		));

		$router->addRoute(
				'adminPostTypeAll', new Zend_Controller_Router_Route_Regex('admin/post-type/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'post-type',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/post-type/all/page/%s.html"
		));

		//CONTENT TYPE CONTROLLER ROUTERS
		$router->addRoute(
				'content_type_id', new Zend_Controller_Router_Route_Regex('admin/content-type/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'content-type',
			'action' => 'id',
			1 => 0), array(
			'content_type_id' => 1,
				), "admin/content-type/%d.html"
		));

		$router->addRoute(
				'content_type_action', new Zend_Controller_Router_Route_Regex('admin/content-type/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'content-type',
			'action' => 'id',
			1 => 0), array(
			'content_type_id' => 1,
			'action' => 2,
				), "admin/content-type/%d/%s.html"
		));

		$router->addRoute(
				'content_type_all', new Zend_Controller_Router_Route_Regex('admin/content-type/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'content-type',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/content-type/all/page/%s.html"
		));

		// EVENT CONTROLLER ROUTERS
		$router->addRoute(
				'event_id', new Zend_Controller_Router_Route_Regex('admin/event/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'event',
			'action' => 'id',
			1 => 0), array(
			'event_id' => 1,
				), "admin/event/%d.html"
		));

		$router->addRoute(
				'event_action', new Zend_Controller_Router_Route_Regex('admin/event/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'event',
			'action' => 'id',
			1 => 0), array(
			'event_id' => 1,
			'action' => 2,
				), "admin/event/%d/%s.html"
		));

		$router->addRoute(
				'event_all', new Zend_Controller_Router_Route_Regex('admin/event/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'event',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/event/all/page/%s.html"
		));

		//COUNTRY CONTROLLER ROUTERS
		$router->addRoute(
				'country_id', new Zend_Controller_Router_Route_Regex('admin/country/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'country',
			'action' => 'id',
			1 => 0), array(
			'country_id' => 1,
				), "admin/country/%d.html"
		));

		$router->addRoute(
				'country_action', new Zend_Controller_Router_Route_Regex('admin/country/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'country',
			'action' => 'id',
			1 => 0), array(
			'country_id' => 1,
			'action' => 2,
				), "admin/country/%d/%s.html"
		));

		$router->addRoute(
				'country_all', new Zend_Controller_Router_Route_Regex('admin/country/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'country',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/country/all/page/%s.html"
		));

		//TEAM CONTROLLER ROUTERS
		$router->addRoute(
				'team_id', new Zend_Controller_Router_Route_Regex('admin/team/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'team',
			'action' => 'id',
			1 => 0), array(
			'team_id' => 1,
				), "admin/team/%d.html"
		));

		$router->addRoute(
				'team_action', new Zend_Controller_Router_Route_Regex('admin/team/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'team',
			'action' => 'id',
			1 => 0), array(
			'team_id' => 1,
			'action' => 2,
				), "admin/team/%d/%s.html"
		));

		$router->addRoute(
				'team_all', new Zend_Controller_Router_Route_Regex('admin/team/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'team',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/team/all/page/%s.html"
		));

		//TRACK CONTROLLER ROUTERS
		$router->addRoute(
				'track_id', new Zend_Controller_Router_Route_Regex('admin/track/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'track',
			'action' => 'id',
			1 => 0), array(
			'track_id' => 1,
				), "admin/track/%d.html"
		));

		$router->addRoute(
				'track_action', new Zend_Controller_Router_Route_Regex('admin/track/(\d+)/(\w*)\.html', array(
			'module' => 'admin',
			'controller' => 'track',
			'action' => 'id',
			1 => 0), array(
			'track_id' => 1,
			'action' => 2,
				), "admin/track/%d/%s.html"
		));

		$router->addRoute(
				'track_all', new Zend_Controller_Router_Route_Regex('admin/track/all/page/(\d+)\.html', array(
			'module' => 'admin',
			'controller' => 'track',
			'action' => 'all',
			1 => 1
				), array(
			'page' => 1,
				), "admin/track/all/page/%s.html"
		));
	}

}
