<?php

class App_Controller_LoaderController extends Zend_Controller_Action
{

    public function preDispatch()
    {
        $this->messages = $this->_helper->getHelper('MessageManager');
        $this->db = $this->_helper->getHelper('DB');
      }

    public function init()
    {
        // configure main menu
//		$uri = $this->_request->getPathInfo();

//		 $activeNav = $this->view->navigation($this->view->main_menu)->findByUri($uri);
//		  if ($activeNav != NULL) {
//		  $activeNav->active = true;
//		  }

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
            $storageData = Zend_Auth::getInstance()->getStorage()->read();
            $user->setLastActivity($storageData['UserID']);

            //checked role
            /* $user_role = $user->getUserStatus($storageData->id);

              switch ($user_role) {
              case 'DISABLE':
              Zend_Auth::getInstance()->clearIdentity();
              Zend_Session::forgetMe();
              return $this->_helper->redirector('login', 'user');
              break;
              } */
        }
    }

}
