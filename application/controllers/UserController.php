<?php

class UserController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/user.css"));
    }

    public function idAction() {
        $request = $this->getRequest();
        $user_id = $request->getParam('id');

        $user = new Application_Model_DbTable_User();

        $user_data = $user->getUserData($user_id);

        if ($user_data) {
            $this->view->user = $user_data;

            $this->view->headTitle($this->view->translate('Пилот'));
            $this->view->headTitle($user_data->login);

            //Zend_Controller_Action_HelperBroker::addPrefix('App_Action_Helpers');
            //$this->view->avatar = $this->_helper->getHelper('GetUserAvatar')->get_image($user_data->id, 1);

            $this->view->avatar = $this->view->setupUserAvatar($user_data->id, $user_data->avatar_type);
        } else {
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Пользователь не существует!'));
            $this->view->errMessage = $this->view->translate("Пользователь не существует");
        }
    }

    public function loginAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        // page title
        $this->view->headTitle($this->view->translate('Авторизация'));

        $request = $this->getRequest();
        $form = new Application_Form_User_Login();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $user = new Application_Model_DbTable_User();
                $user_status = $user->checkUserStatus($form->loginemail->getValue());

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
                                    // Получить объект Zend_Session_Namespace
                                    require_once('Zend/Session/Namespace.php');
                                    $session = new Zend_Session_Namespace('Zend_Auth');
                                    // Установить время действия залогинености
                                    $session->setExpirationSeconds(60 * 60 * 24 * 5);
                                    
                                    Zend_Session::rememberMe(60 * 60 * 24 * 5);
                                }
                                $this->_helper->redirector('index', 'index');
                                break;
                            default:
                                $this->view->errMessage .= $this->view->translate('Вы ввели неверное имя пользователя или пароль. Повторите ввод.') . '<br />';
                                $this->view->errMessage .= '<strong><a href="' . $this->view->baseUrl('user/restore-passwd') . '">' . $this->view->translate('Забыли пароль?') . '</a></strong><br/>'
                                        . '<strong><a href="' . $this->view->baseUrl('user/register') . '">' . $this->view->translate('Зарегистрироваться?') . '</a></strong>';
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
                        $this->view->errMessage .= '<a href="' . $this->view->baseUrl('user/restore-passwd') . '">' . $this->view->translate('Забыли пароль?') . '</a><br/>'
                                . '<strong><a href="' . $this->view->baseUrl('user/register') . '">' . $this->view->translate('Зарегистрироваться?') . '</a></strong>';
                        break;
                }
            } else {
                $this->view->errMessage .= '<strong><a href="' . $this->view->baseUrl('user/restore-passwd') . '">' . $this->view->translate('Забыли пароль?') . '</a></strong><br/>'
                        . '<strong><a href="' . $this->view->baseUrl('user/register') . '">' . $this->view->translate('Зарегистрироваться?') . '</a></strong>';
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
        $form = new Application_Form_User_Register();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $user = new Application_Model_DbTable_User();

                $user_data = array();

                $user_data['login'] = $form->getValue('login');
                $user_data['email'] = $form->getValue('email');
                $user_data['user_role_id'] = 3;
                $user_data['country_id'] = 1;
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
        $form = new Application_Form_User_Activate();

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
                                . $this->view->translate('Авторизоваться?') . '</a></strong>';
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
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }
        $this->view->headTitle($this->view->translate('Восстановление пароля'));

        $request = $this->getRequest();
        $form = new Application_Form_User_RestorePasswd();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                Zend_Controller_Action_HelperBroker::addPrefix('App_Action_Helpers');
                $user_code_restore_pass = $this->_helper->getHelper('GenerateCode')->GenerateCodeString(8);

                $user = new Application_Model_DbTable_User();
                $user->setRestorePassCode($form->getValue('email'), $user_code_restore_pass);

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
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        $this->view->headTitle($this->view->translate('Создание нового пароля'));

        $request = $this->getRequest();
        $form = new Application_Form_User_SetRestorePasswd();

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

    public function editAction() {
        // page title
        $this->view->headTitle($this->view->translate('Редактирование профиля'));

        $request = $this->getRequest();
        $form = new Application_Form_User_Edit();

        $user = new Application_Model_DbTable_User();

        $form->isValid($request->getPost());

        if ($this->getRequest()->isPost()) {
            if ($form->isValidPartial($request->getPost())) {

                $user_where = $user->getAdapter()->quoteInto('id = ?', Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);
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

                                $user_avatar_file = $user->getUserAvatarLoad(Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);
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
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для сохранения изминений профиля!');
            }
        }

        $user_data = $user->fetchRow(array('id = ?' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id));

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


            $country = new Application_Model_DbTable_Country();
            $countries = $country->getCountriesName('ASC');

            foreach ($countries as $country):
                $form->country->addMultiOption($country->id, $country->name);
            endforeach;

            $form->country->setValue($user_data->country_id);
        } else {
            $this->view->errMessage .= $this->view->translate('Произошла ошибка! Свяжитесь с администратором для ее устранения.');
        }

        $this->view->form = $form;
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Гонщики'));
        // pager settings
        $page_count_items = 9;
        $page = $this->getRequest()->getParam('page');
        $page_range = 5;
        $items_order = 'ASC';

        $user = new Application_Model_DbTable_User();
        $this->view->paginator = $user->getSimpleEnableUsersPager($page_count_items, $page, $page_range, $items_order);
    }

    public function messageAction() {
        // page title
        $this->view->headTitle($this->view->translate('Сообщения'));
    }

    public function settingsAction() {
        // page title
        $this->view->headTitle($this->view->translate('Настройки профиля'));

        $request = $this->getRequest();
        $form = new Application_Form_User_Settings();

        $user = new Application_Model_DbTable_User();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                switch ($request->getParam('tab_name')) {
                    case 'lang_settings ':
                        $this->view->errMessage .= "Сожалеем, но данный функционал пока не доступен." . '<br />';
                        break;
                    case 'change_password':
                        if ($form->getValue('newpassword') == $form->getValue('confirmnewpassword') && ($form->getValue('newpassword') != '')) {
                            $user_data = $user->setNewUserPassword(Zend_Auth::getInstance()->getStorage('online-racing')->read()->id, $form->getValue('oldpassword'), $form->getValue('newpassword'));

                            if (!$user_data) {
                                $this->view->errMessage .= "Старый пароль введен не верно! Повторите ввод." . '<br />';
                            } else {
                                $this->view->succMessage .= "Пароль успешно изменен." . '<br />';

                                $user_data = $user->getUserData(Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);

                                // load e-mail script (template) for user
                                $html = new Zend_View();
                                $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
                                // e-mail template values for user
                                $html->assign('login', $user_data['login']);
                                $html->assign('password', $form->getValue('newpassword'));
                                $html->assign('user_id', Zend_Auth::getInstance()->getStorage('online-racing')->read()->id);
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
                            $this->view->errMessage .= "Поля нового пароля должны содержать одинаковые значения и не должны быть пустыми!" . '<br />';
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