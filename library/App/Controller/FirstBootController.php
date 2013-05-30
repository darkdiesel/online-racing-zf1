<?php

class App_Controller_FirstBootController extends Zend_Controller_Action {

    public function init() {
        //setup global variables
        $this->messageManager = $this->_helper->getHelper('MessageManager');

        // configure main menu
        $uri = $this->_request->getPathInfo();
        
        

        /* $activeNav = $this->view->navigation($this->view->main_menu)->findByUri($uri);
          if ($activeNav != NULL) {
          $activeNav->active = true;
          } */

        // configure breadcrumb
        if (($this->_request->getControllerName() . '/' . $this->_request->getActionName()) == "index/index") {
            Zend_Registry::set('breadcrumb', array('show' => FALSE));
            Zend_Registry::set('slider', array('show' => TRUE));
        } else {
            Zend_Registry::set('breadcrumb', array('show' => TRUE));
            Zend_Registry::set('slider', array('show' => FALSE));

             
        }

        if (Zend_Auth::getInstance()->hasIdentity()) {
            $user = new Application_Model_DbTable_User();

            //save last activity time
            $storage_data = Zend_Auth::getInstance()->getStorage()->read();
            $user->setLastActivity($storage_data->id);

            //checked role
            $user_role = $user->getUserStatus($storage_data->id);

            switch ($user_role) {
                case 'DISABLE':
                    Zend_Auth::getInstance()->clearIdentity();
                    Zend_Session::forgetMe();
                    return $this->_helper->redirector('login', 'user');
                    break;
            }
        }
    }

}