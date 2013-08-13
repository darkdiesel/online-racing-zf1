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
		'resourceId', new Zend_Controller_Router_Route_Regex('admin/resource/(\d+)\.html', array(
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
    }

}
