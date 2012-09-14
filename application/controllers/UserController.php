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
        $roles = new Application_Model_RoleMapper();
        $this->view->entries = $roles->fetchAll();
    }
	
	public function loginAction()
	{
        
        if (Zend_Auth::getInstance()->getStorage()->read()->status != 'guest') {
            // если да, то делаем редирект, чтобы исключить многократную авторизацию
            $this->_helper->redirector('index', 'index');
        }

		$request = $this->getRequest();
		$form    = new Application_Form_UserLoginForm();

        if ($form->isValid($this->getRequest()->getPost())){
            $bootstrap = $this->getInvokeArg('bootstrap');
            $auth = Zend_Auth::getInstance();
            $auth->setStorage(new Zend_Auth_Storage_Session('online-racing'));
            $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
            $authAdapter = new Zend_Auth_Adapter_DbTable(
				$adapter, 'user', 'email', 
				'password'
			);
            $authAdapter->setIdentity($form->email->getValue());
            $authAdapter->setCredential($form->password->getValue());
            $result = $auth->authenticate($authAdapter);

            switch ($result->getCode()) {
                case Zend_Auth_Result::SUCCESS:
                    /** Выполнить действия при успешной аутентификации **/
                    $storage = $auth->getStorage('online-racing');
                    $storage_data = $authAdapter->getResultRowObject(
                                             array('login','id'),
                                                                null);
                    //$user_model = new Application_Model_DbTable_User();
                    //$language_model = new Application_Model_DbTable_Language();
                    $storage_data->status = 'user';
                    $storage->write($storage_data);
                    $this->_helper->redirector('index', 'index');
                    break;
             
                default:
                    /** Выполнить действия для остальных ошибок **/
                    //rewrite session for guest
                    $storage_data = new stdClass();
                    $storage_data->status = 'guest';
                    Zend_Auth::getInstance()->getStorage()->write($storage_data);

                    $this->view->errMessage = 'Вы ввели неверное имя пользователя или неверный пароль';
                    break;
            }
		}
		$this->view->form = $form;
	}
	
    public function registrationAction()
    {
        $request = $this->getRequest();
        $form    = new Application_Form_UserRegistrationForm();
        
        

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    
                    $mapper  = new Application_Model_UserMapper();

                    if ($mapper->emailIsAvailable($form->email->getValue()))
                    {
                        $user = new Application_Model_User($form->getValues());
                        
                        // Function to generate random string
                        function generatePassword($length = 8){
                          $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
                          $numChars = strlen($chars);
                          $string = '';
                          for ($i = 0; $i < $length; $i++) {
                            $string .= substr($chars, rand(1, $numChars) - 1, 1);
                          }
                          return $string;
                        }

                        $user->activate = generatePassword(8);
                        $user->enabled = 0;
                        $user->role_id = 3;

                        $mapper->AddNewUser($user);
                        return $this->_helper->redirector('confirm','user');
                    } else {
                        echo "User Already Exist";
                    }
                }
            }

        $this->view->form = $form;
    }

    public function confirmAction()
    {
        $request = $this->getRequest();
        $form    = new Application_Form_UserConfirmForm();

        if($form->isvalid($this->getRequest()->getPost()))
        {

        }

        $this->view->form = $form;
    }

	public function logoutAction()
	{
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('/user/login');
	}
	
	public function infoAction(){
	   $auth = Zend_Auth::getInstance();
        // Если пользователь аутентифицирован
        if ($auth->hasIdentity()){
            // Считываем данные о пользователе
            $user_data = $auth->getStorage()->read();
        }
        
        $this->view->user_data = $user_data;
	}
}