<?php

class AuthController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
    }

    public function signInAction()
    {
        // set layout without sidebar
        $this->_helper->layout->setLayout('layout-default-no-sidebar');

        if (Zend_Auth::getInstance()->hasIdentity()) {
            $defaultIndexUrl = $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), 'default');
            $this->redirect($defaultIndexUrl);
        }

        $this->view->headTitle($this->view->translate('Авторизация'));
        $this->view->pageTitle($this->view->translate('Авторизация'));

        // info message
        $this->messages->addInfo($this->view->translate('Введите данные в форму ниже, чтобы авторизоваться на сайте.'));

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
                            $this->messages->addSuccess($this->view->translate('Вы успешно авторизовались на сайте.'));


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

                            $this->messages->addError($this->view->translate('Вы ввели неверное имя пользователя или пароль. Повторите ввод.')
                                . '<br/><a class="btn btn-danger btn-sm" href="' . $defaultUserRestorePassUrl . '">' . $this->view->translate('Забыли пароль?') . '</a>'
                                . ' <a class="btn btn-danger btn-sm" href="' . $defaultAuthSignUpUrl . '">' . $this->view->translate('Зарегистрироваться?') . '</a>');
                        }

                        break;
                    case USER_STATUS_BLOCKED:
                        $this->messages->addError($this->view->translate('Пользователь заблокирован! Обротитесь к администрации сайта для справки.'));
                        break;
                    case USER_STATUS_NOT_ACTIVATED:
                        $defaultUserActivateUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default');
                        $this->messages->addError($this->view->translate('Пользователь с этими данными не активирован!')
                            . ' <a class="btn btn-danger btn-sm" href="' . $defaultUserActivateUrl . '">' . $this->view->translate('Активировать?') . '</a>');
                        break;
                    case USER_STATUS_NOT_FOUND:

                        $defaultUserRestorePassUrl = $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default');
                        $defaultAuthSignUpUrl = $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-up'), 'default');

                        $this->messages->addError($this->view->translate('Пользователь с этими данными не найден!')
                            . '<br/><a class="btn btn-danger btn-sm" href="' . $defaultUserRestorePassUrl . '">' . $this->view->translate('Забыли пароль?') . '</a>'
                            . ' <a class="btn btn-danger btn-sm" href="' . $defaultAuthSignUpUrl . '">' . $this->view->translate('Зарегистрироваться?') . '</a>');
                        break;
                }
            } else {
                $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
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

}
