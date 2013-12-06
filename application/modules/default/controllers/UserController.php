<?php

class UserController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Гонщик'));
	}

	public function idAction() {
		$request = $this->getRequest();
		$user_id = $request->getParam('user_id');

		$user = new Application_Model_DbTable_User();

		$user_data = $user->getUserData($user_id);

		if ($user_data) {
			$this->view->user = $user_data;
			$this->view->breadcrumb()->UserAll('1')->User($user_id, $user_data->login);

			$this->view->headTitle($user_data->login);
			$this->view->pageTitle($user_data->login);

			$this->view->avatar = $this->view->setupUserAvatar($user_data->id, $user_data->avatar_type);
		} else {
			$this->messages->addError("{$this->view->translate("Пользователь не существует!")}");
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Пользователь не существует!')}");
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	public function activateAction() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
		}

		// page title
		$this->view->headTitle($this->view->translate('Активация пользователя'));
		$this->view->pageTitle($this->view->translate('Активация пользователя'));

		$this->messages->addInfo($this->view->translate('Вам на почту высланы данные для подверждения регистрации. Введите их в форму ниже, чтобы активировать свой аккаунт.'));
		$this->messages->addInfo($this->view->translate('<strong>P.S.</strong> Если вы не нашли письмо, <strong>проверьте папку спам</strong> и пометьте, что письмо не является спамом.'));

		$request = $this->getRequest();
		$form = new Application_Form_User_Activate();
		$form->setAction(
				$this->view->url(
						array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default', true
				)
		);

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {

				$user = new Application_Model_DbTable_User();
				$user_data = array(
					'email' => $form->email->getValue(),
					'password' => sha1($form->password->getValue()),
					'code_activate' => $form->code_activate->getValue(),
				);

				$result = $user->activateUser($user_data['email'], $user_data['password'], $user_data['code_activate']);

				switch ($result) {
					case 'done':
						// load e-mail script (template) for user
						$html = new Zend_View();
						$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
						// e-mail template values for user
						$html->assign('login', $user_data['email']);
						$html->assign('content', 'Ваш профиль активирован. Приятного время провождения на нашем портале. Да прибудет с вами скорость! ©');
						// e-mail for user
						$mail = new Zend_Mail('UTF-8');
						$bodyText = $html->render('activation_template.phtml');
						$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
						$mail->setSubject('Online-Racing.net - Ваш профиль активирован.');
						$mail->addTo($user_data['email'], $user_data['email']);
						$mail->setBodyHtml($bodyText);
						$mail->send();

						// load e-mail script (template) for admin
						$html = new Zend_View();
						$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
						// e-mail template values for admin
						$html->assign('login', "Администратор Online-racing.net");
						$html->assign('content', 'На сайте активирован новый пользователь.<br/>' .
								'Данные пользователя:<br/><br/>' .
								'E-mail: <strong>' . $user_data['email'] . '</strong><br/>');
						// e-mail for admin
						$mail = new Zend_Mail('UTF-8');
						$bodyText = $html->render('master_user_activate_template.phtml');
						$mail->addTo('igor.peshkov@gmail.com', 'Igor Peshkov');
						$mail->setSubject('Online-Racing.net - На сайте активирован пользователь.');
						$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
						$mail->setBodyHtml($bodyText);
						$mail->send();

						$bootstrap = $this->getInvokeArg('bootstrap');
						$auth = Zend_Auth::getInstance();
						$adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
						$authAdapter = new Zend_Auth_Adapter_DbTable(
								$adapter, 'user', 'email', 'password'
						);

						$authAdapter->setIdentity($user_data['email']);
						$authAdapter->setCredential($user_data['password']);
						$result = $auth->authenticate($authAdapter);

						$storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
						$storage = $auth->getStorage();
						$storage->write($storage_data);

						$this->messages->clearMessages();
						$this->messages->addSuccess($this->view->translate('Ваш профиль активирован. Добро пожаловать в команду портала Online-Racing.Net. Желаем отличного настроение и высоких результатов.'));
						$this->messages->addSuccess($this->view->translate('Да прибудет с вами скорость. ©'));

						$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));

						break;
					case 'error':
						$this->messages->addError($this->view->translate('Введены неверные данные активации!'));

						break;
					case 'activate':
						$url_login = $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), 'default', true);
						$this->messages->addError($this->view->translate('Пользователь уже активирован!') . ' <strong><a class="btn btn-default" href="' . $url_login . '">'
								. $this->view->translate('Авторизоваться?') . '</a></strong>');
						break;
					case 'notFound':
						$this->messages->addError($this->view->translate('Пользователь на сайте не найден!'));
						break;
				}
			} else {
				$this->messages->addError($this->view->translate('Исправте ошибки для корректной активации профиля!'));
			}
		}

		$this->view->form = $form;
	}

	public function restorePassAction() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->_helper->redirector('index', 'index');
		}
		$this->view->headTitle($this->view->translate('Восстановление пароля'));
		$this->view->pageTitle($this->view->translate('Восстановление пароля'));

		$this->messages->addInfo($this->view->translate('Для восстановления своего пароля введите e-mail адрес, указаный при регистрации, на который вам будут высланы данные для восстановления пароля.'));

		$request = $this->getRequest();
		$form = new Application_Form_User_RestorePass();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default', true));

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				Zend_Controller_Action_HelperBroker::addPrefix('App_Action_Helpers');
				$user_code_restore_pass = $this->_helper->getHelper('GenerateCode')->GenerateCodeString(8);

				$user = new Application_Model_DbTable_User();
				$user->setRestorePassCode($form->getValue('email'), $user_code_restore_pass);

				// load e-mail script (template) for user
				$html = new Zend_View();
				$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
				// e-mail template values for user
				$restore_url = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'set-restore-pass'), 'default', true);
				$html->assign('login', $form->getValue('email'));
				$html->assign('content', 'Уважаемый пользователь, вы или кто-то другой запросили код для создания нового пароля.<br/>' .
						'На <a href="' . $restore_url . '">странице</a> для создания нового пароля введите следующие данные:<br/><br/>' .
						'E-mail: <strong>' . $form->getValue('email') . '</strong><br/>' .
						'Код восстановления: <strong>' . $user_code_restore_pass . '</strong><br/>' .
						'Если вы не запрашивали новый пароль, то просто проигнорируйте данное сообщение.');
				// e-mail for user
				$mail = new Zend_Mail('UTF-8');
				$bodyText = $html->render('restore_passwd_template.phtml');
				$mail->addTo($form->getValue('email'), $form->getValue('email'));
				$mail->setSubject('Online-Racing.net - Код для восстановления пароля.');
				$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
				$mail->setBodyHtml($bodyText);
				$mail->send();
				
				//clear all messages on the page
				$this->messages->clearMessages();

				$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'set-restore-pass'), 'default', true));
			} else {
				$this->messages->addError($this->view->translate('Исправте следующие ошибки для восстановления пароля!'));
			}
		}

		$this->view->form = $form;
	}

	public function setRestorePassAction() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->_helper->redirector('index', 'index');
		}

		$this->view->headTitle($this->view->translate('Создание нового пароля'));
		$this->view->pageTitle($this->view->translate('Создание нового пароля'));
		
		$this->messages->addInfo($this->view->translate('Введите данные, полученные на ваш регистрационный e-mail в форму ниже для создания нового пароля.'));
		$this->messages->addInfo($this->view->translate('Если вы не получали писем - проверьте папку спам на его наличия в ней и <strong>обозначте его как <u>"не смам"</u></strong>.'));

		$request = $this->getRequest();
		$form = new Application_Form_User_SetRestorePass();
		$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'set-restore-pass'), 'default', true));

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$user = new Application_Model_DbTable_User();

				$user_data = array(
					'email' => $form->getValue('email'),
					'code_restore' => $form->getValue('code_restore'),
					'password' => sha1($form->getValue('password')),
				);

				$result = $user->restoreNewPasswd($user_data['email'], $user_data['code_restore'], $user_data['password']);

				if ($result) {
					// load e-mail script (template) for user
					$html = new Zend_View();
					$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
					// e-mail template values for user
					$html->assign('login', $user_data['email']);
					$html->assign('content', 'Ваш пароль изменен. Приятного время на нашем портале. <br/> Да прибудем с Вами скорость! ©');
					// e-mail for user
					$mail = new Zend_Mail('UTF-8');
					$bodyText = $html->render('set_restore_pass_template.phtml');
					$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
					$mail->setSubject('Online-Racing.net - Ваш пароль изменен.');
					$mail->addTo($user_data['email'], $user_data['email']);
					$mail->setBodyHtml($bodyText);
					$mail->send();

					$bootstrap = $this->getInvokeArg('bootstrap');
					$auth = Zend_Auth::getInstance();
					$adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
					$authAdapter = new Zend_Auth_Adapter_DbTable(
							$adapter, 'user', 'email', 'password'
					);

					$authAdapter->setIdentity($user_data['email']);
					$authAdapter->setCredential($user_data['password']);
					$result = $auth->authenticate($authAdapter);

					$storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
					$storage = $auth->getStorage();
					$storage->write($storage_data);
					
					//clear all messages on the page
					$this->messages->clearMessages();

					$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
				} else {
					$this->messages->addError($this->view->translate('Введены неверные данные для создания нового пароля!'));
					$this->messages->addError('<strong><a href="' . $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default', true) . '">' . $this->view->translate('Выслать данные еще раз?') . '</a></strong>');
				}
			} else {
				$this->messages->addError($this->view->translate('Исправте следующие ошибки для создания пароля!'));
			}
		}

		$this->view->form = $form;
	}

	public function editAction() {
		// page title
		$this->view->headTitle($this->view->translate('Редактирование профиля'));
		$this->view->pageTitle($this->view->translate('Редактирование профиля'));

		$request = $this->getRequest();
		$form = new Application_Form_User_Edit();

		$user = new Application_Model_DbTable_User();

		$form->isValid($request->getPost());
		$form->setAction(
				$this->view->url(
						array('module' => 'default', 'controller' => 'user', 'action' => 'edit'), 'default', true
				)
		);

		if ($this->getRequest()->isPost()) {
			if ($form->isValidPartial($request->getPost())) {

				$user_where = $user->getAdapter()->quoteInto('id = ?', Zend_Auth::getInstance()->getStorage()->read()->id);
				$date = date('Y-m-d H:i:s');
				switch ($request->getParam('tab_name')) {
					case 'avatar':
						if ($form->getValue('avatar_load')) {
							if ($form->avatar_load->receive()) {
								$file = $form->avatar_load->getFileInfo();
								$ext = pathinfo($file['avatar_load']['name'], PATHINFO_EXTENSION);
								$newName = Date('Y-m-d_H-i-s') . strtolower('_avatar' . '.' . $ext);

								$filterRename = new Zend_Filter_File_Rename(array('target'
									=> $file['avatar_load']['destination'] . '/' . $newName, 'overwrite' => true));

								$filterRename->filter($file['avatar_load']['destination'] . '/' . $file['avatar_load']['name']);

								$user_data = array(
									'avatar_load' => '/img/data/users/avatars/' . $newName,
								);

								$user_avatar_file = $user->getUserAvatarLoad(Zend_Auth::getInstance()->getStorage()->read()->id);
								if ($user_avatar_file) {
									unlink(APPLICATION_PATH . '/../public_html' . $user_avatar_file);
								}
							}
						}

						$user_data['avatar_type'] = $form->getValue('avatar_type');
						$user_data['avatar_link'] = $form->getValue('avatar_link');
						$user_data['avatar_gravatar_email'] = $form->getValue('avatar_gravatar_email');
						$user_data['date_edit'] = $date;

						$user->update($user_data, $user_where);
						break;
					case 'personal_Inf':
						$user_data = array(
							'name' => $form->getValue('name'),
							'surname' => $form->getValue('surname'),
							'birthday' => $form->getValue('birthday'),
							'country_id' => $form->getValue('country'),
							'city' => $form->getValue('city'),
							//'flag' => $form->getValue('flag'),
							'date_edit' => $date,
						);

						$user->update($user_data, $user_where);
						break;
					case 'contacts_Inf':
						$user_data = array(
							'skype' => $form->getValue('skype'),
							'icq' => $form->getValue('icq'),
							'gtalk' => $form->getValue('gtalk'),
							'www' => $form->getValue('www'),
							'date_edit' => $date,
						);

						$user->update($user_data, $user_where);
						break;
					case 'additional_Inf':
						$user_data = array(
							'about' => $form->getValue('about'),
							'date_edit' => $date,
						);

						$user->update($user_data, $user_where);
						break;
					default:
						$this->view->errMessage .= $this->view->translate('Приносим Вам наши извинения, но сахранение этих данных пока не работает. Пожалуйста, зайдите через некоторое время.');
						break;
				}
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$user_data = $user->fetchRow(array('id = ?' => Zend_Auth::getInstance()->getStorage()->read()->id));

		if (count($user_data) != 0) {
			$form->name->setValue($user_data->name);
			$form->surname->setValue($user_data->surname);
			$form->birthday->setValue($user_data->birthday);
			$form->city->setValue($user_data->city);
			$form->avatar_type->setValue($user_data->avatar_type);
			//$form->avatar_load->setValue($user_data->avatar_load);
			$form->avatar_link->setValue($user_data->avatar_link);
			$form->avatar_gravatar_email->setValue($user_data->avatar_gravatar_email);
			$form->skype->setValue($user_data->skype);
			$form->icq->setValue($user_data->icq);
			$form->gtalk->setValue($user_data->gtalk);
			$form->www->setValue($user_data->www);
			$form->about->setValue($user_data->about);
			$this->view->user_id = $user_data->id;


			$countries = $this->db->get('country')->getAll(FALSE, array('id', 'native_name', 'english_name'));

			foreach ($countries as $country):
				$form->country->addMultiOption($country->id, $country->native_name . " ({$country->english_name})");
			endforeach;

			$form->country->setValue($user_data->country_id);
		} else {
			$this->view->errMessage .= $this->view->translate('Произошла ошибка! Свяжитесь с администратором для ее устранения.');
		}

		$this->view->form = $form;
	}

	public function allAction() {
		$this->view->headTitle($this->view->translate('Все'));
		$this->view->pageTitle($this->view->translate('Гонщики'));
		// pager settings
		$page_count_items = 12;
		$page = $this->getRequest()->getParam('page');
		$page_range = 10;
		$items_order = 'DESC';

		$this->view->breadcrumb()->UserAll($page);

		$user = new Application_Model_DbTable_User();
		$this->view->paginator = $user->getSimpleEnableUsersPager($page_count_items, $page, $page_range, $items_order);
	}

	public function messageAction() {
		// page title
		$this->view->headTitle($this->view->translate('Сообщения'));
		$this->view->pageTitle($this->view->translate('Сообщения'));

		$this->messages->addInfo($this->view->translate('Приносим свои извинения. Функционал данной страницы находится в разработке!'));
	}

	public function settingsAction() {
		// page title
		$this->view->headTitle($this->view->translate('Настройки профиля'));
		$this->view->pageTitle($this->view->translate('Настройки профиля'));

		$this->messages->addInfo($this->view->translate('Измините настройки и нажмите "Сохранить".'));

		echo $this->message;

		$request = $this->getRequest();

		$form = new Application_Form_User_Settings();
		$form->setAction(
				$this->view->url(
						array('module' => 'default', 'controller' => 'user', 'action' => 'settings'), 'default', true
				)
		);

		$user = new Application_Model_DbTable_User();

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				switch ($request->getParam('tab_name')) {
					case 'lang_settings ':
						$this->messages->addInfo($this->view->translate('Приносим свои извинения. Функционал данной страницы находится в разработке!'));
						break;
					case 'change_password':
						if ($form->getValue('newpassword') == $form->getValue('confirmnewpassword') && ($form->getValue('newpassword') != '')) {
							$user_data = $user->setNewUserPassword(Zend_Auth::getInstance()->getStorage()->read()->id, $form->getValue('oldpassword'), $form->getValue('newpassword'));

							if (!$user_data) {
								$this->messages->addError($this->view->translate("Старый пароль введен не верно! Повторите ввод."));
							} else {
								$this->messages->addSuccess($this->view->translate("Пароль успешно изменен."));

								$user_data = $user->getUserData(Zend_Auth::getInstance()->getStorage()->read()->id);

								// load e-mail script (template) for user
								$html = new Zend_View();
								$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
								// e-mail template values for user
								$html->assign('login', $user_data['login']);
								$html->assign('password', $form->getValue('newpassword'));
								$html->assign('user_id', Zend_Auth::getInstance()->getStorage()->read()->id);
								// e-mail for user
								$mail = new Zend_Mail('UTF-8');
								$bodyText = $html->render('new_user_password_template.phtml');
								$mail->addTo($user_data['email'], $user_data['email']);
								$mail->setSubject('Online-Racing.net - Пароль учетной записи был изминен.');
								$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
								$mail->setBodyHtml($bodyText);
								$mail->send();
							}
						} else {
							$this->messages->addError($this->view->translate("Поля нового пароля должны содержать одинаковые значения и не должны быть пустыми!"));
						}
						break;
					default :

						break;
				}
			} else {
				$this->view->errMessage .= "Исправте следующие ошибки для смены настроек!" . '<br />';
			}
		}

		$this->view->form = $form;
	}

}
