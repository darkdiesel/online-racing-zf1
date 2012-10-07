<?php

class UserController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/user.css"));
    }

    public function indexAction() {
        // action body
        $roles = new Application_Model_RoleMapper();
        $this->view->entries = $roles->fetchAll();
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
                            case '1':
                                //print message
                                $this->view->errMessage = 'Пользователь с этими данными не активирован! Перейдите на <a href="' . $this->view->baseUrl('user/activate') . '">страницу</a> для активации.';
                                break;
                            case '2':
                                //print message
                                $this->view->errMessage = 'Пользователь с этими данными заблокирован! Абротитесь к администрации сайта для разблокировки.';
                                break;
                            default:
                                $storage = $auth->getStorage('online-racing');
                                $storage->write($storage_data);
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

                // Function to generate random string
                function generatePassword($length = 8) {
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
                    //$user_model = new Application_Model_DbTable_User();
                    $storage_data->status = 'user';
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
        return $this->_helper->redirector('login', 'user');
    }

    public function infoAction() {
        // page title
        $this->view->storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
        $this->view->headTitle($this->view->translate('Просмотр профиля'));
        
        $request = $this->getRequest();
        $this->view->id = $request->getParam('id');
        
        $mapper = new Application_Model_UserMapper();
        $this->view->user = $mapper->getUserById($this->view->id);
        
    }

    public function messageAction() {
        // page title
        $this->view->headTitle($this->view->translate('Сообщения'));
    }

    public function settingsAction() {
        // page title
        $this->view->headTitle($this->view->translate('Настройки профиля'));
    }

    public function editAction() {
        // page title
        $this->view->headTitle($this->view->translate('Редактирование профиля'));
    }

}