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
		$request = $this->getRequest();
		$form    = new Application_Form_UserLoginForm();
		
		/*if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
			
				return $this->_helper->redirector('index');
			}
		}*/
		
        if ($form->isValid($this->getRequest()->getPost())){
            $bootstrap = $this->getInvokeArg('bootstrap');
            $auth = Zend_Auth::getInstance();
            $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
            $authAdapter = new Zend_Auth_Adapter_DbTable(
                                                       $adapter, 'user', 'login', 
                                                       'password', 'MD5(?)'
                                                  );
            $authAdapter->setIdentity($form->email->getValue());
            $authAdapter->setCredential($form->password->getValue());
            $result = $auth->authenticate($authAdapter);
			print_r($result);
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
		
		
		$this->view->form = $form;
	}
	
	public function logoutAction()
	{
	
	}
	
	public function registrationAction()
	{
		$request = $this->getRequest();
		$form    = new Application_Form_UserRegistrationForm();
		
		$this->view->form = $form;
	}
	
	public function infoAction(){
	
	}
}