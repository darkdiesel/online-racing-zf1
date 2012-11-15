<?php

class UserController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
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

                $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());

                $authAdapter->setTableName('user')
                        ->setIdentityColumn('email')
                        ->setCredentialColumn('password');

                $auth = Zend_Auth::getInstance();
                $auth->setStorage(new Zend_Auth_Storage_Session('online-racing'));

                $authAdapter->setIdentity($form->loginemail->getValue())
                        ->setCredential(sha1($form->loginpassword->getValue()));

                $result = $auth->authenticate($authAdapter);

                switch ($result->getCode()) {
                    case Zend_Auth_Result::SUCCESS:
                        /** Выполнить действия при успешной аутентификации * */
                        $mapper = new Application_Model_UserMapper();
                        $storage_data = $authAdapter->getResultRowObject(
                                array('login', 'id'), null);

                        switch ($mapper->checkUserStatus($storage_data->id)) {
                            case 'notActive':
                                //print message
                                $this->view->errMessage = 'Пользователь с этими данными не активирован! Перейдите на <a href="' . $this->view->baseUrl('user/activate') . '">страницу</a> для активации.';
                                Zend_Auth::getInstance()->clearIdentity();
                                Zend_Session::forgetMe();
                                break;
                            case 'notEnabled':
                                //print message
                                $this->view->errMessage = 'Пользователь с этими данными заблокирован! Абротитесь к администрации сайта для разблокировки.';
                                Zend_Auth::getInstance()->clearIdentity();
                                Zend_Session::forgetMe();
                                break;
                            case 'active':
                                $storage = $auth->getStorage('online-racing');
                                $storage->write($storage_data);

                                //save date for last login
                                $user = new Application_Model_User(array('id' => $storage_data->id));
                                $mapper->save($user, 'last_login');

                                if ($form->remember->getValue() == 1) {
                                    Zend_Session::rememberMe(60 * 60 * 24 * 2);
                                }
                                $this->_helper->redirector('index', 'index');
                                break;
                        }
                        break;
                    default:
                        /** Выполнить действия для остальных ошибок * */
                        $this->view->errMessage .= 'Вы ввели неверное имя пользователя или пароль. Повторите ввод.<br />Забыди <a href="' . $this->view->baseUrl('user/restorepasswd') . '">пароль?</a>';
                        break;
                }
            } else {
                $this->view->errMessage .= 'Забыди <a href="' . $this->view->baseUrl('user/restorepasswd') . '">пароль?</a>';
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

        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.validate.my.js"));
        //$this->view->headScript()->appendFile($this->view->baseUrl("js/script.js"));

        $request = $this->getRequest();
        $form = new Application_Form_UserRegisterForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $user = new Application_Model_User($form->getValues());

                $mapper = new Application_Model_UserMapper();
                
                Zend_Controller_Action_HelperBroker::addPrefix('App_Action_Helpers');
                $user->activate = $this->_helper->getHelper('GenerateCode')->GenerateCodeString(8);

                $mapper->save($user, 'register');

                // load e-mail script (template) for user
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                // e-mail template values for user
                $html->assign('login', $user->login);
                $html->assign('content', 'Спасибо за регистарцию на нашем портале.<br/>' .
                        'На <a href="http://online-racing.net/user/activate">странице</a> для подтверждения регистрации введите следующие данные:<br/><br/>' .
                        'Логин: <strong>' . $user->login . '</strong><br/>' .
                        'E-mail: <strong>' . $user->email . '</strong><br/>' .
                        'Пароль: <strong>' . $user->password . '</strong><br/>' .
                        'Код активации: <strong>' . $user->activate . '</strong><br/>');
                // e-mail for user
                $mail = new Zend_Mail('UTF-8');
                $bodyText = $html->render('register_template.phtml');
                $mail->addTo($user->email, $user->email);
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
                        'Логин: <strong>' . $user->login . '</strong><br/>' .
                        'E-mail: <strong>' . $user->email . '</strong><br/>');
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
                $this->view->errMessage .= "Исправте ошибки для корректной регистрации!";
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
                $mapper = new Application_Model_UserMapper();

                $userEmail = $form->email->getValue();
                $userPassword = sha1($form->password->getValue());
                $userConfirmCode = $form->confirmCode->getValue();

                if ($mapper->activateUserByCode($userEmail, $userPassword, $userConfirmCode) == 1) {

                    // load e-mail script (template) for user
                    $html = new Zend_View();
                    $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                    // e-mail template values for user
                    $html->assign('login', $userEmail);
                    $html->assign('content', 'Ваш профиль активирован. Приятного время провождения на нашем портале.');
                    // e-mail for user
                    $mail = new Zend_Mail('UTF-8');
                    $bodyText = $html->render('activation_template.phtml');
                    $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');
                    $mail->setSubject('Online-Racing.net - Ваш профиль активирован.');
                    $mail->addTo($userEmail, $userEmail);
                    $mail->setBodyHtml($bodyText);
                    $mail->send();

                    // load e-mail script (template) for admin
                    $html = new Zend_View();
                    $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                    // e-mail template values for admin
                    $html->assign('login', "Глава сайта Online-racing.net");
                    $html->assign('content', 'На сайте активирован новый пользователь.<br/>' .
                            'Данные пользователя:<br/><br/>' .
                            'E-mail: <strong>' . $userEmail . '</strong><br/>');
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

                    $authAdapter->setIdentity($userEmail);
                    $authAdapter->setCredential($userPassword);
                    $result = $auth->authenticate($authAdapter);

                    $mapper = new Application_Model_UserMapper();
                    $storage_data = $authAdapter->getResultRowObject(array('login', 'id'), null);

                    $storage = $auth->getStorage('online-racing');
                    //save date for last login
                    $user = new Application_Model_User(array('id' => $storage_data->id));
                    $mapper->save($user, 'last_login');

                    $storage->write($storage_data);

                    return $this->_helper->redirector('login', 'user');
                } else {
                    $this->view->errMessage .= 'Введены неверные данные активации.<br>';
                }
            } else {
                $this->view->errMessage .= "Исправте ошибки для корректной активации профиля!";
            }
        }

        $this->view->form = $form;
    }

    public function restorepasswdAction() {
        // page title
        $this->view->headTitle($this->view->translate('Восстановление пароля'));

        $request = $this->getRequest();
        $form = new Application_Form_UserRestorePasswdForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $this->view->errMessage = $this->params['email'];
            } else {
                $this->view->errMessage .= "Исправте следующие ошибки для востановления пароля!";
            }
        }

        $this->view->form = $form;
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::forgetMe();
        return $this->_helper->redirector('login', 'user');
    }

    public function infoAction() {
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
                $this->view->errMessage .= "Исправте следующие ошибки для сохранения изминений профиля!";
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

        $request = $this->getRequest();
        
        $mapper = new Application_Model_UserMapper();
                
        $this->view->paginator = $mapper->getUsersPager(9, $request->getParam('page'), 5);
    }

}