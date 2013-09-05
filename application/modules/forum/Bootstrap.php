<?php

class Forum_Bootstrap extends Zend_Application_Module_Bootstrap
{

    protected function _initPlugins()
    {
	$frontController = Zend_Controller_Front::getInstance();
	$frontController->registerPlugin(new App_Plugin_Module_Forum($this));
    }

}