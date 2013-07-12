<?php

/**
 * Module's bootstrap file. 
 * Notice the bootstrap class' name is "Modulename_"Bootstrap. 
 * When creating your own modules make sure that you are using the correct namespace 
 */
class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{

    /*protected function _bootstrap()
    {
	//Now let's parse the module specific configuration  
	//Path might change however this is probably the one you won't ever need to change...  
	//And also don't forget to use the current staging environment by sending the APP_ENV parameter to the Zend_Config  
	$_conf = new Zend_Config_Ini(APPLICATION_PATH . "/modules/" . $this->getModuleName() . "/config/application.ini", APPLICATION_ENV);
	$this->_options = array_merge($this->_options, $_conf->toArray()); //Let's merge the both arrays so that we can use them together...  
	parent::_bootstrap(); //Well our custom bootstrap logic should end with the actual bootstrapping, now that we have merged both configs, we can go on...  
    }*/

}
