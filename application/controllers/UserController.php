<?php
class UserController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }
	
	public function loginAction()
	{
        
        if (Zend_Auth::getInstance()->getStorage()->read()->status != 'guest') {
            // если да, то делаем редирект, чтобы исключить многократную авторизацию
            $this->_helper->redirector('index', 'index');
        }

		$request = $this->getRequest();
		$form    = new Application_Form_UserLoginForm();

		/*if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
			
				return $this->_helper->redirector('index');
			}
		}*/
		

		/*
			$form = new Application_Form_Enter();
        if ($form->isValid($this->getRequest()->getPost())){
            $bootstrap = $this->getInvokeArg('bootstrap');
            $auth = Zend_Auth::getInstance();
            $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
            $authAdapter = new Zend_Auth_Adapter_DbTable(
                                                       $adapter, 'user', 'login', 
                                                       'password', 'MD5(?)'
                                                  );
            $authAdapter->setIdentity($form->login->getValue());
            $authAdapter->setCredential($form->password->getValue());
            $result = $auth->authenticate($authAdapter);
            // Если валидация прошла успешно сохраняем в storage инфу о пользователе
            if ($result->isValid()){
                $storage = $auth->getStorage();
                $storage_data = $authAdapter->getResultRowObject(
                                         null, 
                                         array('activate', 'password', 'enabled'));
                $user_model = new Application_Model_DbTable_User();
                $language_model = new Application_Model_DbTable_Language();
                $storage_data->status = 'user';
                $storage->write($storage_data);
            }
    }
		*/
        if ($form->isValid($this->getRequest()->getPost())){
            $bootstrap = $this->getInvokeArg('bootstrap');
            $auth = Zend_Auth::getInstance();
            $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
            $authAdapter = new Zend_Auth_Adapter_DbTable(
				$adapter, 'user', 'email', 
				'password'
			);
            $authAdapter->setIdentity($form->email->getValue());
            $authAdapter->setCredential($form->password->getValue());
            $result = $auth->authenticate($authAdapter);

            // Если валидация прошла успешно сохраняем в storage инфу о пользователе
            if ($result->isValid()){
                $storage = $auth->getStorage();
                $storage_data = $authAdapter->getResultRowObject(
                                         null, 
                                         array('activate', 'password', 'enabled'));
                //$user_model = new Application_Model_DbTable_User();
                //$language_model = new Application_Model_DbTable_Language();
                $storage_data->status = 'user';
                $storage->write($storage_data);
                $this->_helper->redirector('index', 'index');
            }   else {
                //rewrite session for guest
                $storage_data = new stdClass();
                $storage_data->status = 'guest';
                Zend_Auth::getInstance()->getStorage()->write($storage_data);

            
                $this->view->errMessage = 'Вы ввели неверное имя пользователя или неверный пароль';
            }
		}
		
		
		$this->view->form = $form;
	}
	
	public function logoutAction()
	{
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('/user/login');
	}
	
	public function registrationAction()
	{
		$request = $this->getRequest();
		$form    = new Application_Form_UserRegistrationForm();
		
		$this->view->form = $form;
	}
	
	public function infoAction(){
	   $auth = Zend_Auth::getInstance();
        // Если пользователь аутентифицирован
        if ($auth->hasIdentity()){
            // Считываем данные о пользователе
            $user_data = $auth->getStorage()->read();
        }
        
        return $this->view->user_data = $user_data;
	}
}