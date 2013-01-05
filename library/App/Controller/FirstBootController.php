<?php

class App_Controller_FirstBootController extends Zend_Controller_Action {

    public function init() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $user = new Application_Model_DbTable_User();

            //save last activity time
            $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
            $user->setLastActivity($storage_data->id);

            //checked role
            $user_role = $user->getUserStatus($storage_data->id);

            switch ($user_role) {
                case 'DISABLE':
                    Zend_Auth::getInstance()->clearIdentity();
                    Zend_Session::forgetMe();
                    return $this->_helper->redirector('login', 'user');
                    break;
                case 'master':
                    $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/master_toolbar.css"));
                    $this->view->showPanel = 'master';
                    break;
                case 'admin':
                    $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/master_toolbar.css"));
                    $this->view->showPanel = 'admin';
                    break;
                default :
                    $this->view->showPanel = 'NONE';
                    break;
            }
        }
    }

}