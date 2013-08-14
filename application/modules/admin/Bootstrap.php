<?php

/**
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 * When creating your own modules make sure that you are using the correct namespace 
 */
class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{

    public function _initRoutes()
    {
	$frontController = Zend_Controller_Front::getInstance();
	$router = $frontController->getRouter();

	//resource controller routers
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
		'resource', new Zend_Controller_Router_Route_Regex('admin/resource/(\d+)/(\w*)\.html', array(
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

	//content type controller routers
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
		'content_type', new Zend_Controller_Router_Route_Regex('admin/content-type/(\d+)/(\w*)\.html', array(
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
	
    }

}
