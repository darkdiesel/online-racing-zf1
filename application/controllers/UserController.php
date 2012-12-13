<?php

class UserController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/user.css"));
    }

    public function loginAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        // page title
        $this->view->headTitle($this->view->translate('Авторизация'));

        $request = $this->getRequest();
        $form = new Application_Form_UserLoginForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $user = new Application_Model_DbTable_User();
                $user_status = $user->check_user_status($form->loginemail->getValue());

                switch ($user_status) {
                    case 'enable':
                        $bootstrap = $this->getInvokeArg('bootstrap');
                        $auth = Zend_Auth::getInstance();
                        $auth->setStorage(new Zend_Auth_Storage_Session('online-racing'));
                        $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();

                        $authAdapter = new Zend_Auth_Adapter_DbTable(
                                        $adapter, 'user', 'email', 'password'
                        );

                        $authAdapter->setIdentity($form->getValue('loginemail'));
                        $authAdapter->setCredential(sha1($form->getValue('loginpassword')));
                        $result = $auth->authenticate($authAdapter);

                        switch ($result->getCode()) {
                            case Zend_Auth_Result::SUCCESS:
                                $storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
                                $storage = $auth->getStorage('online-racing');
                                $storage->write($storage_data);

                                if ($form->remember->getValue() == 1) {
                                    Zend_Session::rememberMe(60 * 60 * 24 * 2);
                                }
                                $this->_helper->redirector('index', 'index');
                                break;
                            default:
                                $this->view->errMessage .= $this->view->translate('Вы ввели неверное имя пользователя или пароль. Повторите ввод.') . '<br />';
                                $this->view->errMessage .= '<strong><a href="' . $this->view->baseUrl('user/restorepasswd') . '">' . $this->view->translate('Забыди пароль?') . '</a></strong><br/>'
                                        . '<strong><a href="' . $this->view->baseUrl('user/restorepasswd') . '">Зарегистрироваться?</a></strong>';
                                break;
                        }
                        break;
                    case 'disable':
                        $this->view->errMessage .= $this->view->translate('Пользователь с этими данными заблокирован! Абротитесь к администрации сайта для разблокировки.');
                        break;
                    case 'notActivate':
                        $this->view->errMessage .= $this->view->translate('Пользователь с этими данными не активирован!')
                                . " <strong><a href=" . $this->view->baseUrl('user/activate') . ">" . $this->view->translate('Активировать?') . "</a></strong>";
                        break;
                    case 'notFound':
                        $this->view->errMessage .= $this->view->translate('Пользователь с этими данными не найден!') . '<br/>';
                        $this->view->errMessage .= '<a href="' . $this->view->baseUrl('user/restorepasswd') . '">' . $this->view->translate('Забыди пароль?') . '</a><br/>'
                                . '<strong><a href="' . $this->view->baseUrl('user/restorepasswd') . '">Зарегистрироваться?</a></strong>';
                        break;
                }
            } else {
                $this->view->errMessage .= '<strong><a href="' . $this->view->baseUrl('user/restorepasswd') . '">' . $this->view->translate('Забыди пароль?') . '</a></strong><br/>'
                        . '<strong><a href="' . $this->view->baseUrl('user/restorepasswd') . '">Зарегистрироваться?</a></strong>';
            }
        }
        $this->view->form = $form;
    }

    public function registerAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        // page title
        $this->view->headTitle($this->view->translate('Регистрация'));

        // jQuery validate script
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.validate.my.js"));

        $request = $this->getRequest();
        $form = new Application_Form_UserRegisterForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $user = new Application_Model_DbTable_User();

                $user_data = array();

                $user_data['login'] = $form->getValue('login');
                $user_data['email'] = $form->getValue('email');
                $user_data['user_role_id'] = 3;
                $user_data['flag_id'] = 1; //!!!!!!!!!! доделать
                $user_data['enable'] = 1;

                Zend_Controller_Action_HelperBroker::addPrefix('App_Action_Helpers');
                $user_data['code_activate'] = $this->_helper->getHelper('GenerateCode')->GenerateCodeString(8);

                $user_data['date_create'] = date('Y-m-d H:i:s');
                $user_data['password'] = sha1($form->getValue('password'));

                $newUser = $user->createRow($user_data);
                $newUser->save();

                // load e-mail script (template) for user
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                // e-mail template values for user
                $html->assign('login', $user_data['login']);
                $html->assign('content', 'Спасибо за регистарцию на нашем портале.<br/>' .
                        'На <a href="http://online-racing.net/user/activate">странице</a> для подтверждения регистрации введите следующие данные:<br/><br/>' .
                        'Логин: <strong>' . $user_data['login'] . '</strong><br/>' .
                        'E-mail: <strong>' . $user_data['email'] . '</strong><br/>' .
                        'Пароль: <strong>' . $form->getValue('password') . '</strong><br/>' .
                        'Код активации: <strong>' . $user_data['activate'] . '</strong><br/>');
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
                $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                // e-mail template values for admin
                $html->assign('login', "Глава сайта Online-racing.net");
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

                return $this->_helper->redirector('activate', 'user');
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте ошибки для корректной регистрации!');
            }
        }

        $this->view->form = $form;
    }

    public function activateAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        // page title
        $this->view->headTitle($this->view->translate('Активация пользователя'));

        $request = $this->getRequest();
        $form = new Application_Form_UserActivateForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $user = new Application_Model_DbTable_User();
                $user_data = array(
                    'email' => $form->email->getValue(),
                    'password' => sha1($form->password->getValue()),
                    'code_activate' => $form->code_activate->getValue(),
                );

                $result = $user->activate_user($user_data['email'], $user_data['password'], $user_data['code_activate']);

                switch ($result) {
                    case 'done':
                        // load e-mail script (template) for user
                        $html = new Zend_View();
                        $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                        // e-mail template values for user
                        $html->assign('login', $user_data['email']);
                        $html->assign('content', 'Ваш профиль активирован. Приятного время провождения на нашем портале.');
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
                        $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                        // e-mail template values for admin
                        $html->assign('login', "Админу Online-racing.net");
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
                        $auth->setStorage(new Zend_Auth_Storage_Session('online-racing'));
                        $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
                        $authAdapter = new Zend_Auth_Adapter_DbTable(
                                        $adapter, 'user', 'email', 'password'
                        );

                        $authAdapter->setIdentity($user_data['email']);
                        $authAdapter->setCredential($user_data['password']);
                        $result = $auth->authenticate($authAdapter);

                        $storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
                        $storage = $auth->getStorage('online-racing');
                        $storage->write($storage_data);

                        return $this->_helper->redirector('index', 'index');

                        break;
                    case 'error':
                        $this->view->errMessage .= $this->view->translate('Введены неверные данные активации!');

                        break;
                    case 'activate':
                        $this->view->errMessage .= $this->view->translate('Пользователь уже активирован!') . ' <strong><a href="' . $this->view->baseURL('user/login') . '">'
                                . $this->view->translate('Авторизоваться!') . '</a></strong>';
                        break;
                    case 'notFound':
                        $this->view->errMessage .= $this->view->translate('Пользователь на сайте не найден!');
                        break;
                }
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте ошибки для корректной активации профиля!');
            }
        }

        $this->view->form = $form;
    }

    public function restorePasswdAction() {
        $this->view->headTitle($this->view->translate('Восстановление пароля'));

        $request = $this->getRequest();
        $form = new Application_Form_UserRestorePasswdForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                Zend_Controller_Action_HelperBroker::addPrefix('App_Action_Helpers');
                $user_code_restore_pass = $this->_helper->getHelper('GenerateCode')->GenerateCodeString(8);

                $user = new Application_Model_DbTable_User();
                $user->set_restore_pass_code($form->getValue('email'), $user_code_restore_pass);

                // load e-mail script (template) for user
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                // e-mail template values for user
                $html->assign('login', $form->getValue('email'));
                $html->assign('content', 'Уважаемый пользователь вы или кто-то другой запрасили код для создания нового пароля.<br/>' .
                        'На <a href="http://online-racing.net/user/set-restore-passwd">странице</a> для создания нового пароля введите следующие данные:<br/><br/>' .
                        'E-mail: <strong>' . $form->getValue('email') . '</strong><br/>' .
                        'Код востановления: <strong>' . $user_code_restore_pass . '</strong><br/>' .
                        'Если это не вы запросили новый пароль, то просто проигнорируйте это сообщение.');
                // e-mail for user
                $mail = new Zend_Mail('UTF-8');
                $bodyText = $html->render('restore_passwd_template.phtml');
                $mail->addTo($form->getValue('email'), $form->getValue('email'));
                $mail->setSubject('Online-Racing.net - Код востановления пароля.');
                $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
                $mail->setBodyHtml($bodyText);
                $mail->send();

                return $this->_helper->redirector('set-restore-passwd', 'user');
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для востановления пароля!');
            }
        }

        $this->view->form = $form;
    }

    public function setRestorePasswdAction() {
        $this->view->headTitle($this->view->translate('Создание нового пароля'));

        $request = $this->getRequest();
        $form = new Application_Form_UserSetRestorePasswdForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $user = new Application_Model_DbTable_User();

                $user_data = array(
                    'email' => $form->getValue('email'),
                    'code_restore' => $form->getValue('code_restore'),
                    'password' => sha1($form->getValue('password')),
                );

                $result = $user->restore_passwd($user_data['email'], $user_data['code_restore'], $user_data['password']);

                if ($result) {
                    // load e-mail script (template) for user
                    $html = new Zend_View();
                    $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                    // e-mail template values for user
                    $html->assign('login', $user_data['email']);
                    $html->assign('content', 'Ваш пароль изменен. Приятного время провождения на нашем портале.');
                    // e-mail for user
                    $mail = new Zend_Mail('UTF-8');
                    $bodyText = $html->render('set_restore_passwd_template.phtml');
                    $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
                    $mail->setSubject('Online-Racing.net - Ваш пароль изменен.');
                    $mail->addTo($user_data['email'], $user_data['email']);
                    $mail->setBodyHtml($bodyText);
                    $mail->send();

                    $bootstrap = $this->getInvokeArg('bootstrap');
                    $auth = Zend_Auth::getInstance();
                    $auth->setStorage(new Zend_Auth_Storage_Session('online-racing'));
                    $adapter = $bootstrap->getPluginResource('db')->getDbAdapter();
                    $authAdapter = new Zend_Auth_Adapter_DbTable(
                                    $adapter, 'user', 'email', 'password'
                    );

                    $authAdapter->setIdentity($user_data['email']);
                    $authAdapter->setCredential($user_data['password']);
                    $result = $auth->authenticate($authAdapter);

                    $storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);
                    $storage = $auth->getStorage('online-racing');
                    $storage->write($storage_data);

                    return $this->_helper->redirector('index', 'index');
                } else {
                    $this->view->errMessage .= $this->view->translate('Введены неверные данные для создания нового пароля!') . '<br>'
                            . '<strong><a href="' . $this->view->baseUrl('user/restore-passwd') . '">' . $this->view->translate('Выслать данные еще раз?') . '</a></strong>';
                }
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте ошибки для корректного создания пароля!');
            }
        }

        $this->view->form = $form;
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::forgetMe();
        return $this->_helper->redirector('login', 'user');
    }

    public function idAction() {
        // page title
        //$this->view->storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
        $this->view->headTitle($this->view->translate('Просмотр профиля'));

        $request = $this->getRequest();
        $user_id = $request->getParam('id');

        if ($request->getParam('id') == 0) {
            $this->view->errMessage = "Пользователь не существует";
            return;
        } else {
            
        }

        $mapper = new Application_Model_UserMapper();
        $user_data = $mapper->getUserDataById($user_id);

        if ($user_data == 'null') {
            $this->view->errMessage = "Пользователь не существует";
            return;
        } else {
            $this->view->user = $user_data;
            $this->view->gravatar = $this->view->gravatar()
                    ->setEmail($user_data->gravatar)
                    ->setImgSize(200)
                    ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                    ->setSecure(true)
                    ->setAttribs(array('class' => 'img-polaroid', 'title' => $user_data->login . " - profile avatar"));
        }
    }

    public function messageAction() {
        // page title
        $this->view->headTitle($this->view->translate('Сообщения'));
    }

    public function settingsAction() {
        // page title
        $this->view->headTitle($this->view->translate('Настройки профиля'));

        $request = $this->getRequest();
        $form = new Application_Form_UserSettingsForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                
            } else {
                $this->view->errMessage .= "Исправте следующие ошибки для востановления пароля!";
            }
        }

        $this->view->form = $form;
    }

    public function editAction() {
        // page title
        $this->view->headTitle($this->view->translate('Редактирование профиля'));

        $request = $this->getRequest();
        $form = new Application_Form_UserEditForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $userMapper = new Application_Model_UserMapper();
                $user = new Application_Model_User(array('id' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id));

                switch ($request->getParam('tab_name')) {
                    case 'avatar':
                        $user->setGravatar($form->gravatar->getValue());
                        $userMapper->save($user, 'avatar');
                        break;
                    case 'personal_Inf':
                        $user->setName($form->name->getValue());
                        $user->setSurname($form->surname->getvalue());
                        $user->setBirthday($form->birthday->getValue());
                        $user->setCountry($form->country->getValue());
                        $user->setCity($form->city->getValue());
                        $userMapper->save($user, 'personal_Inf');
                        break;
                    case 'contacts_Inf':
                        $user->setSkype($form->skype->getValue());
                        $user->setIcq($form->icq->getValue());
                        $user->setGtalk($form->gtalk->getValue());
                        $user->setWww($form->www->getvalue());
                        $userMapper->save($user, 'contacts_Inf');
                        break;
                    case 'additional_Inf':
                        $user->setAbout($form->about->getValue());
                        $userMapper->save($user, 'additional_Inf');
                        break;
                    default:
                        $this->view->errMessage .= $form->gravatar->getValue() . "Приносим Вам наши извинения, но сахранение этих данных пока не работает. Пожалуйста, зайдите через некоторое время.";
                        break;
                }
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для сохранения изминений профиля!');
            }
        }

        $mapper = new Application_Model_UserMapper();
        $user_data = $mapper->getUserDataById(Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);

        $form->name->setValue($user_data->name);
        $form->surname->setValue($user_data->surname);
        $form->birthday->setValue($user_data->birthday);
        $form->country->setValue($user_data->country);
        $form->city->setValue($user_data->city);
        $form->gravatar->setValue($user_data->gravatar);
        $form->skype->setValue($user_data->skype);
        $form->icq->setValue($user_data->icq);
        $form->gtalk->setValue($user_data->gtalk);
        $form->www->setValue($user_data->www);
        $form->about->setValue($user_data->about);

        $this->view->form = $form;
        $this->view->gravatar = $this->view->gravatar()
                ->setEmail($user_data->gravatar)
                ->setImgSize(200)
                ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                ->setSecure(true)
                ->setAttribs(array('class' => 'img-polaroid', 'title' => " - profile avatar"));
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Гонщики'));
        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';

        $page = $this->getRequest()->getParam('page');

        $user = new Application_Model_DbTable_User();
        $this->view->paginator = $user->get_users_pager($page_count_items, $page, $page_range, $items_order);

        /* $mapper = new Application_Model_UserMapper();

          $this->view->paginator = $mapper->getUsersPager(9, $request->getParam('page'), 5, 'all'); */
    }

}