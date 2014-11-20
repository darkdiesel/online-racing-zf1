<?php

class AuthController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    public function signInAction()
    {
        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

        if (Zend_Auth::getInstance()->hasIdentity()) {
            $defaultIndexUrl = $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default');
            $this->redirect($defaultIndexUrl);
        }

        $this->view->headTitle($this->view->t('Авторизация'));
        $this->view->pageTitle($this->view->t('Авторизация'));

        // info message
        $this->messages->addInfo($this->view->t('Введите данные в форму ниже, чтобы авторизоваться на сайте.'));

        $authSignInForm = new Peshkov_Form_Auth_SignIn();

        $this->view->authSignInForm = $authSignInForm;
        $this->view->displayHeaderAuthoSignInForm = FALSE;

        if ($this->getRequest()->isPost()) {
            if ($authSignInForm->isValid($this->getRequest()->getPost())) {

                $query = Doctrine_Query::create()
                    ->from('Default_Model_User u')
                    ->leftJoin('u.UserRole ur')
                    ->leftJoin('ur.Role r')
                    ->leftJoin('u.Country c')
                    ->where('u.Status = ?', 1)
                    ->where('u.Email = ?', $authSignInForm->getValue('Email'));
                $userResult = $query->fetchArray();

                if (count($userResult) == 1) {
                    $userStatus = $this->view->getUser()->checkUserStatus($userResult[0]);
                } else {
                    $userStatus = USER_STATUS_NOT_FOUND;
                }

                switch ($userStatus) {
                    case USER_STATUS_ENABLE:
                        $auth = Zend_Auth::getInstance();

                        //create auth adapter
                        $authAdapter = new Peshkov_Auth_Adapter_Doctrine('Default_Model_User');

                        $authAdapter->setIdentityColumn('Email')
                            ->setCredentialColumn('Password');

                        $authAdapter->setIdentity($authSignInForm->getValue('Email'))
                            ->setCredential(sha1($authSignInForm->getValue('Password')));

                        //get result from authntication
                        $result = $auth->authenticate($authAdapter);

//                        switch ($result->getCode()) {
//                            case Zend_Auth_Result::SUCCESS:
//                        }

                        if ($result->isValid()) {

                            $storageData = $authAdapter->getResultRowObject(array('ID'), null);

                            $auth->getStorage()->write(array(
                                'UserID' => $storageData->ID
                            ));

                            // Receive Zend_Session_Namespace object
                            //require_once('Zend/Session/Namespace.php');
                            // Set the time of user logged in
                            if ($authSignInForm->getValue('RememberMe') == 1) {
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

                            $userID = $storageData->ID;
                            $userIP = $this->view->getUser()->getUserIP();

                            // Update Last User Login IP
                            $updatedUser = Doctrine_Core::getTable('Default_Model_User')->find($userID);

                            $newUserData = array(
                                'LastUserLoginIP' => $userIP,
                            );

                            $updatedUser->fromArray($newUserData);
                            $updatedUser->save();

                            $this->view->showMessages()->clearMessages();
                            $this->messages->addSuccess($this->view->t('Вы успешно авторизовались на сайте.'));


                            // Check access for CKEditor
                            $ckfinder = $this->view->checkUserAccess('ckfinder');

                            if ($ckfinder) {
                                $ckFinderSession = new Zend_Session_Namespace('CKFinder');
                                /** Enable CKFinder * */
                                $ckFinderSession->allowed = true;
                            } else {
                                $ckFinderSession = new Zend_Session_Namespace('CKFinder');
                                /** Disable CKFinder * */
                                $ckFinderSession->allowed = false;
                            }

                            $this->redirect($this->getRequest()->getParam('redirectTo'));
                        } else {
                            $defaultAuthSignUpUrl = $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-up'), 'default');
                            $defaultUserRestorePassUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default');

                            $this->messages->addError($this->view->t('Вы ввели неверное имя пользователя или пароль. Повторите ввод.')
                                . '<br/><a class="btn btn-danger btn-sm" href="' . $defaultUserRestorePassUrl . '">' . $this->view->t('Забыли пароль?') . '</a>'
                                . ' <a class="btn btn-danger btn-sm" href="' . $defaultAuthSignUpUrl . '">' . $this->view->t('Зарегистрироваться?') . '</a>');
                        }

                        break;
                    case USER_STATUS_BLOCKED:
                        $this->messages->addError($this->view->t('Пользователь заблокирован! Обротитесь к администрации сайта для справки.'));
                        break;
                    case USER_STATUS_NOT_ACTIVATED:
                        $defaultUserActivateUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default');
                        $this->messages->addError($this->view->t('Пользователь с этими данными не активирован!')
                            . ' <a class="btn btn-danger btn-sm" href="' . $defaultUserActivateUrl . '">' . $this->view->t('Активировать?') . '</a>');
                        break;
                    case USER_STATUS_NOT_FOUND:

                        $defaultUserRestorePassUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default');
                        $defaultAuthSignUpUrl = $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-up'), 'default');

                        $this->messages->addError($this->view->t('Пользователь с этими данными не найден!')
                            . '<br/><a class="btn btn-danger btn-sm" href="' . $defaultUserRestorePassUrl . '">' . $this->view->t('Забыли пароль?') . '</a>'
                            . ' <a class="btn btn-danger btn-sm" href="' . $defaultAuthSignUpUrl . '">' . $this->view->t('Зарегистрироваться?') . '</a>');
                        break;
                }
            } else {
                $this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }
    }

    /**
     * function logoutAction
     * TODO: Redirect to previous page after sign out.
     */
    public function signOutAction()
    {
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

        $defaultIndexUrl = $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default');

        // Redirect to main page
        $this->redirect($defaultIndexUrl);
    }

    public function signUpAction()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->redirect($this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default', true));
        }

        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

        $this->view->headTitle($this->view->t('Регистрация'));
        $this->view->pageTitle($this->view->t('Регистрация'));

        $this->messages->addInfo($this->view->t('Введите данные в форму ниже, чтобы зарегистрироваться на сайте.'));

        // jQuery validate script
        $this->view->MinifyHeadScript()->appendFile("/library/jquery.validate/jquery.validate.min.js");

        $authSignUpForm = new Peshkov_Form_Auth_SignUp();
        $this->view->authSignUpForm = $authSignUpForm;

        if ($this->getRequest()->isPost()) {
            if ($authSignUpForm->isValid($this->getRequest()->getPost())) {
                $date = date('Y-m-d H:i:s');

                $newUser = new Default_Model_User();
                $newUser->fromArray($authSignUpForm->getValues());

                $newUser->Password = sha1($newUser->Password);
                $newUser->Status = 1;
                $newUser->AvatarType = USER_AVATAR_TYPE_NONE;
                $newUser->DateCreate = $date;

                $newUser->ActivationCode = $this->view->GenerateCode(8);

                $newUser->save();

                $defaultUserActivateUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default');
                $defaultUserIDUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'id', 'userID' => $newUser->getIncremented()), 'defaultUserID');

                // load e-mail script (template) for user
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');

                // Assign e-mail template variables
                $html->assign('Name', $newUser->FirstName);
                $html->assign('Surname', $newUser->LastName);
                $html->assign('NickName', $newUser->NickName);
                $html->assign('Email', $newUser->Email);
                $html->assign('Password', $authSignUpForm->getValue('Password'));
                $html->assign('ActivationCode', $newUser->ActivationCode);
                $html->assign('ActivateUrl', $defaultUserActivateUrl);

                // New Mail for registered user
                $mail = new Zend_Mail('UTF-8');

                $mail->addTo($newUser->Email, $newUser->NickName);
                $mail->setSubject('Online-Racing.net - Код подверждения регистрации.');
                //TODO: Get back-email from site setting.
                $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');

                $bodyText = $html->render('user-complete-registration.phtml');
                $mail->setBodyHtml(mb_convert_encoding($bodyText, 'UTF-8', 'UTF-8'));

                $mail->send();

                // load e-mail script (template) for admin
                $html = new Zend_View();
                $html->setScriptPath(APPLICATION_PATH . '/modules/default/views/emails/');

                // Assign e-mail template variables
                //TODO: Get admin nick from admin from site setting.
                $html->assign('NickName', "Администратор Online-Racing.Net");
                $html->assign('Name', $newUser->FirstName);
                $html->assign('Surname', $newUser->LastName);
                $html->assign('NickName', $newUser->NickName);
                $html->assign('Email', $newUser->Email);
                $html->assign('ActivationCode', $newUser->ActivationCode);
                $html->assign('NewUserUrl', $defaultUserIDUrl);

                // New Mail for site administrator
                $mail = new Zend_Mail('UTF-8');

                //TODO: Get admin email from site setting.
                $mail->addTo('igor.peshkov@gmail.com', 'Igor Peshkov');
                $mail->setSubject('Online-Racing.net - Зарегестрировался новый пользователь.');
                //TODO: Get back-email from site setting.
                $mail->setFrom('onlinera@online-racing.net', 'Online-Racing.net');

                $bodyText = $html->render('administrator-user-registration.phtml');
                $mail->setBodyHtml(mb_convert_encoding($bodyText, 'UTF-8', 'UTF-8'));

                $mail->send();

                $this->redirect($defaultUserActivateUrl);
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }


    }

}
