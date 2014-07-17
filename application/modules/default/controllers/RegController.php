<?php

class RegController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
	}

	public function userAction() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
		}

        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

		// page title
		$this->view->headTitle($this->view->translate('Регистрация пользователя'));
		$this->view->pageTitle($this->view->translate('Регистрация пользователя'));

		$this->messages->addInfo("{$this->view->translate('Введите данные в форму ниже, чтобы зарегистрироваться на сайте.')}");

		// jQuery validate script
		$this->view->MinifyHeadScript()->appendFile("/js/jquery.validate.my.js");

		$request = $this->getRequest();
		$form = new Application_Form_Reg_User();
		$form->setAction(
				$this->view->url(
						array('module' => 'default', 'controller' => 'reg', 'action' => 'user'), 'default', true
				)
		);

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {

				$user = new Application_Model_DbTable_User();

				$user_data = array();

				$user_data['login'] = $form->getValue('login');
				$user_data['email'] = $form->getValue('email');
				$user_data['avatar_type'] = 0;
				//$user_data['user_role_id'] = 3;
				$user_data['country_id'] = 1;
				$user_data['enable'] = 1;

				$user_data['code_activate'] = $this->_helper->getHelper('GenerateCode')->GenerateCodeString(8);

				$user_data['date_create'] = date('Y-m-d H:i:s');
				$user_data['password'] = sha1($form->getValue('password'));

				$newUser = $user->createRow($user_data);
				$newUser->save();

				// load e-mail script (template) for user
				$html = new Zend_View();
				$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
				// e-mail template values for user
				$activate_url = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default', true);
				$html->assign('login', $user_data['login']);
				$html->assign('content', 'Спасибо за регистарцию на нашем портале.<br/>' .
						'На <a href="' . $activate_url . '">странице</a> для подтверждения регистрации введите следующие данные:<br/><br/>' .
						'Логин: <strong>' . $user_data['login'] . '</strong><br/>' .
						'E-mail: <strong>' . $user_data['email'] . '</strong><br/>' .
						'Пароль: <strong>' . $form->getValue('password') . '</strong><br/>' .
						'Код активации: <strong>' . $user_data['code_activate'] . '</strong><br/>');
				// e-mail for user
				$mail = new Zend_Mail('UTF-8');
				$bodyText = $html->render('register_template.phtml');
				$mail->addTo($user_data['email'], $user_data['email']);
				$mail->setSubject('Online-Racing.net - Код подверждения регистрации.');
				$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
				$mail->setBodyHtml($bodyText);
				$mail->send();

				// load e-mail script (template) for admin
				$html = new Zend_View();
				$html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');
				// e-mail template values for admin
				$html->assign('login', "Администратор Online-racing.net");
				$html->assign('content', 'На сайте появился новый пользователь.<br/>' .
						'Регистрационные данные:<br/><br/>' .
						'Логин: <strong>' . $user_data['login'] . '</strong><br/>' .
						'E-mail: <strong>' . $user_data['email'] . '</strong><br/>');
				// e-mail for admin
				$mail = new Zend_Mail('UTF-8');
				$bodyText = $html->render('master_user_add_template.phtml');
				$mail->addTo('igor.peshkov@gmail.com', 'Igor Peshkov');
				$mail->setSubject('Online-Racing.net - На сайте новый пользователь.');
				$mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
				$mail->setBodyHtml($bodyText);
				$mail->send();

				$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default', true));
			} else {
				$this->view->errMessage .= $this->view->translate('Исправте ошибки для корректной регистрации!');
			}
		}

		$this->view->form = $form;
	}

}
