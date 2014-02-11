<?php

class AuthController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
	}

	public function loginAction() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
		}

		// page title
		$this->view->headTitle($this->view->translate('Авторизация'));
		$this->view->pageTitle($this->view->translate('Авторизация'));

		// info message
		$this->messages->addInfo("{$this->view->translate('Введите данные в форму ниже, чтобы авторизоваться на сайте.')}");

		$request = $this->getRequest();
		$form = new Application_Form_Auth_Login();


		// Set  redirect url if user authorizing successful. 
		$redirectToUrl = $request->getParam('redirectTo');

		if (empty($redirectToUrl)) {
			$redirectToUrl = $this->_helper->getHelper('UserServerData')->GetPreviousPage();

			$config = Zend_Registry::get('config');

			if (parse_url($redirectToUrl, PHP_URL_HOST) == parse_url($config->resources->frontController->baseUrl, PHP_URL_HOST)) {
				$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), 'default', true) . "?redirectTo=" . $redirectToUrl);
			} else {
				$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), 'default', true));
			}
		} else {
			$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), 'default', true) . "?redirectTo=" . $redirectToUrl);
		}

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$user = new Application_Model_DbTable_User();
				$user_status = $user->checkUserStatus($form->loginemail->getValue());

				switch ($user_status) {
					case 'enable':
						$auth = Zend_Auth::getInstance();

						//create auth adapter
						$authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());

						//set user credential for authentication
						$authAdapter->setTableName('user')
								->setIdentityColumn('email')
								->setCredentialColumn('password');

						$authAdapter->setIdentity($form->getValue('loginemail'));
						$authAdapter->setCredential(sha1($form->getValue('loginpassword')));

						//get result from authntication
						$result = $auth->authenticate($authAdapter);

						if ($result->isValid()) {
							$storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
							$auth->getStorage()->write($storage_data);

							// Receive Zend_Session_Namespace object
							//require_once('Zend/Session/Namespace.php');
							// Set the time of user logged in
							if ($form->remember->getValue() == 1) {
								$session = new Zend_Session_Namespace('Zend_Auth');
								$session->setExpirationSeconds(60 * 60 * 120);

								Zend_Session::rememberMe(60 * 60 * 120);
								Zend_Session::setOptions(array(
									'cookie_lifetime' => 60 * 60 * 120,
									'gc_maxlifetime' => 60 * 60 * 120));
								setcookie('RememberMe', 1, 60 * 60 * 120, '/');
							} else {
								setcookie('RememberMe', 0, 0, '/');
								Zend_Session::forgetMe();
							}

							$user = new Application_Model_DbTable_User();

							$user_ip = $this->_helper->getHelper('UserServerData')->GetUserIp();

							$new_user_data['last_login_ip'] = $user_ip;
							$user_id = Zend_Auth::getInstance()->getStorage()->read()->id;

							$user_where = $user->getAdapter()->quoteInto('id = ?', $user_id);
							$user->update($new_user_data, $user_where);

							$this->view->showMessages()->clearMessages();
							$this->messages->addSuccess("{$this->view->translate('Вы успешно авторизовались на сайте.')}");

							$ckfinder = $this->view->checkUserAccess('ckfinder');

							if ($ckfinder) {
								$ckFinderSession = new Zend_Session_Namespace('CKFinder');
								/** Disable CKFinder * */
								$ckFinderSession->allowed = true;
							} else {
								$ckFinderSession = new Zend_Session_Namespace('CKFinder');
								/** Disable CKFinder * */
								$ckFinderSession->allowed = false;
							}

							$this->redirect($request->getParam('redirectTo'));
						} else {
							$form->populate($request->getPost());
							$reg_url = $this->view->url(array('module' => 'default', 'controller' => 'register', 'action' => 'user'), 'default', true);
							$rest_pass_url = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default', true);

							$this->messages->addError("{$this->view->translate('Вы ввели неверное имя пользователя или пароль. Повторите ввод.')}"
									. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$rest_pass_url}\">{$this->view->translate('Забыли пароль?')}</a>"
									. " <a class=\"btn btn-danger btn-sm\" href=\"{$reg_url}\">{$this->view->translate('Зарегистрироваться?')}</a>");
						}

						/* switch ($result->getCode()) {
						  case Zend_Auth_Result::SUCCESS:
						  $storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
						  $storage = $auth->getStorage();
						  $storage->write($storage_data);

						  if ($form->remember->getValue() == 1) {
						  // Получить объект Zend_Session_Namespace
						  //require_once('Zend/Session/Namespace.php');
						  //$session = new Zend_Session_Namespace('online-racing');
						  // set
						  //$session->setExpirationSeconds(60 * 60 * 24 * 5);

						  Zend_Session::rememberMe(60 * 60 * 24 * 5);
						  } else {
						  Zend_Session::forgetMe();
						  }
						  $this->_helper->redirector('index', 'index');
						  break;
						  default:
						  $form->populate($request->getPost());
						  $this->view->errMessage .= $this->view->translate('Вы ввели неверное имя пользователя или пароль. Повторите ввод.') . '<br />';
						  $this->view->errMessage .= '<strong><a href="' . $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'set-restore-pass'), 'default', true) . '">' . $this->view->translate('Забыли пароль?') . '</a></strong><br/>'
						  . '<strong><a href="' . $this->view->baseUrl('user/register') . '">' . $this->view->translate('Зарегистрироваться?') . '</a></strong>';
						  break;
						  } */
						break;
					case 'disable':
						$this->messages->addError("{$this->view->translate('Пользователь с этими данными заблокирован! Обротитесь к администрации сайта для разблокировки.')}");
						break;
					case 'notActivate':
						$this->messages->addError("{$this->view->translate('Пользователь с этими данными не активирован!')}"
								. " <a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'user', 'action' => 'activate'), 'default', true)}\">{$this->view->translate('Активировать?')}</a>");
						break;
					case 'notFound':
						$this->messages->addError("{$this->view->translate('Пользователь с этими данными не найден!')}"
								. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'user', 'action' => 'restore-pass'), 'default', true)}\">{$this->view->translate('Забыли пароль?')}</a>"
								. " <a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'user', 'action' => 'register'), 'default', true)}\">{$this->view->translate('Зарегистрироваться?')}</a>");
						break;
				}
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}
		$this->view->form = $form;
	}

	/**
	 * function logoutAction
	 * 	@todo Переделать перенаправление на предыдущую страницу после того как пользователь вылогинился
	 */
	public function logoutAction() {
		if (!Zend_Auth::getInstance()->hasIdentity()) {
			$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
		}

		// Disable layout
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		Zend_Auth::getInstance()->clearIdentity();
		Zend_Session::forgetMe();
		Zend_Session::expireSessionCookie();

		$ckFinderSession = new Zend_Session_Namespace('CKFinder');
		/** Disable CKFinder * */
		$ckFinderSession->allowed = false;

		// Redirect to main page
		$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
	}

}
