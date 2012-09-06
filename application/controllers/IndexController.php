<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
		
		$auth = Zend_Auth::getInstance();
		// Если пользователь аутентифицирован
		if ($auth->hasIdentity()){
			// Считываем данные о пользователе
			$user_data = $auth->getStorage()->read();
		}
		
		return $this->view->user_data = $user_data;
    }
}