<?php

/**
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 * When creating your own modules make sure that you are using the correct namespace 
 */
class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initPlugins()
    {
	$frontController = Zend_Controller_Front::getInstance();
	$frontController->registerPlugin(new App_Plugin_Module_Admin($this));
    }
    
    public function _initRoutes()
    {
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
		'user_all', new Zend_Controller_Router_Route_Regex('admin/user/all/page/(\d+)\.html', array(
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
		'resource_id', new Zend_Controller_Router_Route_Regex('admin/resource/(\d+)\.html', array(
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
		'resource_action', new Zend_Controller_Router_Route_Regex('admin/resource/(\d+)/(\w*)\.html', array(
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
		'resource_all', new Zend_Controller_Router_Route_Regex('admin/resource/all/page/(\d+)\.html', array(
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
		'role_id', new Zend_Controller_Router_Route_Regex('admin/role/(\d+)\.html', array(
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
		'role_action', new Zend_Controller_Router_Route_Regex('admin/role/(\d+)/(\w*)\.html', array(
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
		'role_all', new Zend_Controller_Router_Route_Regex('admin/role/all/page/(\d+)\.html', array(
	    'module' => 'admin',
	    'controller' => 'role',
	    'action' => 'all',
	    1 => 1
		), array(
	    'page' => 1,
		), "admin/role/all/page/%s.html"
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
    }

}
