<?php

class App_Controller_FirstBootController extends Zend_Controller_Action {

    public function init() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $mapper = new Application_Model_UserMapper();
            $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
            $user = new Application_Model_User(array('id' => $storage_data->id));
            $mapper->save($user, 'last_login');
        }
    }

}