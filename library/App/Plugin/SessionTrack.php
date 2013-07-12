<?php

class App_Plugin_SessionTrack extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
	$auth = Zend_Auth::getInstance();

	if ($auth->hasIdentity()) { // user is logged in
	    // get an instance of Zend_Session_Namespace used by Zend_Auth
	    $authns = new Zend_Session_Namespace($auth->getStorage()->getNamespace());

	    // set an expiration on the Zend_Auth namespace where identity is held
	    $authns->setExpirationSeconds(60 * 60 * 48);  // expire auth storage after 2 days
	}
    }

}