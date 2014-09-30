<?php

class UserController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Гонщик'));
    }

    // action for view league
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'userID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'userID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_User u')
                ->leftJoin('u.UserRole ur')
                ->leftJoin('ur.Role r')
                ->leftJoin('u.Country c')
                ->where('u.Status = ?', 1)
                ->where('u.ID = ?', $requestData->userID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->userData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle($result[0]['Name']);

                // BreadsCrumbs
                $this->view->breadcrumb()->UserAll('1')->User($result[0]['ID'], $result[0]['NickName']);
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемый профиль не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Профиль не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Профиль не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all users
    public function allAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'page' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'page' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $this->view->headTitle($this->view->translate('Все'));
            $this->view->pageTitle($this->view->translate('Гонщики'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_User u')
                ->leftJoin('u.UserRole ur')
                ->leftJoin('ur.Role r')
                ->leftJoin('u.Country c')
                ->where('u.Status = ?', 1)
                ->orderBy('u.DateLastActivity DESC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $userPaginator = new Zend_Paginator($adapter);
            // pager settings
            $userPaginator->setItemCountPerPage("12");
            $userPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $userPaginator->setPageRange("5");

            if ($userPaginator->count() == 0) {
                $this->view->userData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->userData = $userPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for activate new user
    public function activateAction()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
        }

        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

        // page title
        $this->view->headTitle($this->view->translate('Активация пользователя'));
        $this->view->pageTitle($this->view->translate('Активация пользователя'));

        $this->messages->addInfo(
            $this->view->translate('Вам на почту высланы данные для подверждения регистрации. Введите их в форму ниже, чтобы активировать свой аккаунт.')
        );
        $this->messages->addInfo(
            $this->view->translate('<strong>P.S.</strong> Если вы не нашли письмо, <strong>проверьте папку спам</strong> и пометьте, что письмо не является спамом.')
        );

        $userActivateForm = new Peshkov_Form_User_Activate();
        $this->view->userActivateForm = $userActivateForm;

        if ($this->getRequest()->isPost()) {
            if ($userActivateForm->isValid($this->getRequest()->getPost())) {

                $query = Doctrine_Query::create()
                    ->from('Default_Model_User u')
                    ->where('u.Status = ?', 1)
                    ->where('u.Email = ?', $userActivateForm->getValue('Email'))
                    ->andWhere('u.ActivationCode = ?', $userActivateForm->getValue('ActivationCode'));
                $userResult = $query->fetchArray();

                if (count($userResult) == 1) {
                    if ($userResult[0]['Password'] == sha1($userActivateForm->getValue('Password'))) {
                        if ($userResult[0]['ActivationCode'] == $userActivateForm->getValue('ActivationCode')) {

                            $updatedUser = Doctrine_Core::getTable('Default_Model_User')->find($userResult[0]['ID']);
                            $updatedUser->fromArray($userResult[0]);

                            $updatedUser->ActivationCode = null;

                            $updatedUser->save();

                            $this->messages->clearMessages();
                            $this->messages->addSuccess($this->view->translate('Ваш профиль активирован. '
                                . 'Добро пожаловать в команду Online-Racing.Net. '
                                . 'Желаем отличного настроение и высоких результатов.'));
                            $this->messages->addSuccess($this->view->translate('Да пребудет с вами скорость. ©'));
                            $this->messages->addSuccess($this->view->translate('Для авторизации пользуйтесь формой ниже.'));

                            $defaultAuthSignInUrl = $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-in'), 'default');
                            $defaultUserIDUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'id', 'userID' => $updatedUser->ID), 'defaultUserID');

                            // load e-mail script (template) for user
                            $html = new Zend_View();
                            $html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');

                            // Assign e-mail template variables
                            $html->assign('Name', $updatedUser->Name);
                            $html->assign('Surname', $updatedUser->Surname);
                            $html->assign('NickName', $updatedUser->NickName);
                            $html->assign('SignInUrl', $defaultAuthSignInUrl);

                            // New Mail for registered user
                            $mail = new Zend_Mail('UTF-8');

                            $mail->addTo($updatedUser->Email, $updatedUser->NickName);
                            $mail->setSubject('Online-Racing.net - Ваш профиль активирован.');
                            //TODO: Get back-email from site setting.
                            $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');

                            $bodyText = $html->render('user-complete-activation.phtml');
                            $mail->setBodyHtml(mb_convert_encoding($bodyText, 'UTF-8', 'UTF-8'));

                            $mail->send();

                            // load e-mail script (template) for admin
                            $html = new Zend_View();
                            $html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');

                            // Assign e-mail template variables
                            //TODO: Get admin nick from admin from site setting.
                            $html->assign('NickName', "Администратор Online-Racing.Net");
                            $html->assign('Name', $updatedUser->Name);
                            $html->assign('Surname', $updatedUser->Surname);
                            $html->assign('NickName', $updatedUser->NickName);
                            $html->assign('Email', $updatedUser->Email);
                            $html->assign('NewUserUrl', $defaultUserIDUrl);

                            // New Mail for site administrator
                            $mail = new Zend_Mail('UTF-8');

                            //TODO: Get admin email from site setting.
                            $mail->addTo('igor.peshkov@gmail.com', 'Igor Peshkov');
                            $mail->setSubject('Online-Racing.net - Активирован новый пользователь.');
                            //TODO: Get back-email from site setting.
                            $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');

                            $bodyText = $html->render('administrator-user-activation.phtml');
                            $mail->setBodyHtml(mb_convert_encoding($bodyText, 'UTF-8', 'UTF-8'));

                            $mail->send();

                            $this->redirect($defaultAuthSignInUrl);
                        } else {
                            $this->messages->addError($this->view->translate('Неверный код активации!'));
                        }
                    } else {
                        $this->messages->addError($this->view->translate('Неверные данные авторизации!'));
                    }
                } else {
                    $this->messages->addError($this->view->translate('Пользователь с такими данными не найден!'));
                }
            } else {
                $this->messages->addError($this->view->translate('Исправте ошибки для корректной активации профиля!'));
            }
        }
    }

    // action for activate operation for resseting password
    public function restorePassAction()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

        $this->view->headTitle($this->view->translate('Восстановление пароля'));
        $this->view->pageTitle($this->view->translate('Восстановление пароля'));

        $this->messages->addInfo(
            $this->view->translate('Для восстановления своего пароля введите e-mail адрес, указаный при регистрации, на который вам будут высланы данные для восстановления пароля.')
        );

        $userRestorePassForm = new Peshkov_Form_User_RestorePass();
        $this->view->userRestorePassForm = $userRestorePassForm;

        if ($this->getRequest()->isPost()) {
            if ($userRestorePassForm->isValid($this->getRequest()->getPost())) {
                $query = Doctrine_Query::create()
                    ->from('Default_Model_User u')
                    ->where('u.Status = ?', 1)
                    ->where('u.Email = ?', $userRestorePassForm->getValue('Email'));
                $userResult = $query->fetchArray();

                if (count($userResult) == 1) {
                    $date = date('Y-m-d H:i:s');

                    $updatedUser = Doctrine_Core::getTable('Default_Model_User')->find($userResult[0]['ID']);

                    $updatedUser->fromArray($userResult[0]);

                    $updatedUser->RestorePassCode = $this->view->GenerateCode(8);
                    $updatedUser->DateExperateRestorePassCode = date("Y-m-d H:i:s", strtotime($date . "+15 minutes"));

                    $updatedUser->save();


                    $defaultUserNewPassUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'new-pass'), 'default');

                    // load e-mail script (template) for user
                    $html = new Zend_View();
                    $html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');

                    // Assign e-mail template variables
                    $html->assign('Name', $updatedUser->Name);
                    $html->assign('Surname', $updatedUser->Surname);
                    $html->assign('NickName', $updatedUser->NickName);
                    $html->assign('Email', $updatedUser->Email);
                    $html->assign('RestorePassCode', $updatedUser->RestorePassCode);
                    $html->assign('NewPassUrl', $defaultUserNewPassUrl);

                    // New Mail for registered user
                    $mail = new Zend_Mail('UTF-8');

                    $mail->addTo($updatedUser->Email, $updatedUser->NickName);
                    $mail->setSubject('Online-Racing.net - Ваш профиль активирован.');
                    //TODO: Get back-email from site setting.
                    $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');

                    $bodyText = $html->render('user-restore-pass-template.phtml');
                    $mail->setBodyHtml(mb_convert_encoding($bodyText, 'UTF-8', 'UTF-8'));

                    $mail->send();

                    $this->redirect($defaultUserNewPassUrl);
                } else {
                    $this->messages->addError($this->view->translate('Пользователь с такими данными не найден!'));
                }

            } else {
                $this->messages->addError($this->view->translate('Исправте следующие ошибки для восстановления пароля!'));
            }
        }
    }

    public function newPassAction()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector('index', 'index');
        }

        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

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

    public function editAction()
    {
        // page title
        $this->view->headTitle($this->view->translate('Редактирование профиля'));
        $this->view->pageTitle($this->view->translate('Редактирование профиля'));

        $this->messages->addInfo($this->view->translate('Введите новые данные и нажмите "Сохранить".'));

        $request = $this->getRequest();

        $user = new Application_Model_DbTable_User();

        $form = new Application_Form_User_Edit();
        $form->isValid($request->getPost());
        $form->setAction($this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'edit'), 'default', true));

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
                                    'avatar_load' => '/data-content/data-uploads/user/avatar_upload/' . $newName,
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
                        $this->messages->addWarning($this->view->translate('Приносим Вам наши извинения, но сахранение этих данных пока не работает. Пожалуйста, зайдите через некоторое время.'));
                        break;
                }
            } else {
                $this->messages->addWarning($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        $user_data = $user->fetchRow(array('id = ?' => Zend_Auth::getInstance()->getStorage()->read()->id));

        if (count($user_data) != 0) {
            $form->name->setValue($user_data->name);
            $form->surname->setValue($user_data->surname);
            $form->birthday->setValue($user_data->birthday);
            $form->city->setValue($user_data->city);
            $form->avatar_type->setValue($user_data->avatar_type);
            $form->avatar_link->setValue($user_data->avatar_link);
            $form->avatar_gravatar_email->setValue($user_data->avatar_gravatar_email);
            $form->skype->setValue($user_data->skype);
            $form->icq->setValue($user_data->icq);
            $form->gtalk->setValue($user_data->gtalk);
            $form->www->setValue($user_data->www);
            $form->about->setValue($user_data->about);
            $this->view->user_id = $user_data->id;

            $countries = $this->db->get('country')->getAll(FALSE, array('id', 'NativeName', 'EnglishName'));

            foreach ($countries as $country):
                $form->country->addMultiOption($country->id, $country->NativeName . " ({$country->EnglishName})");
            endforeach;

            $form->country->setValue($user_data->country_id);
        } else {
            $this->messages->addWarning($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
        }

        $this->view->form = $form;
    }

    public function messageAction()
    {
        $this->view->headTitle($this->view->translate('Сообщения'));
        $this->view->pageTitle($this->view->translate('Сообщения'));

        $this->messages->addInfo($this->view->translate('Приносим свои извинения. Функционал данной страницы находится в разработке!'));
    }

    public function settingsAction()
    {
        // page title
        $this->view->headTitle($this->view->translate('Настройки профиля'));
        $this->view->pageTitle($this->view->translate('Настройки профиля'));

        $this->messages->addInfo($this->view->translate('Измините настройки и нажмите "Сохранить".'));

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
