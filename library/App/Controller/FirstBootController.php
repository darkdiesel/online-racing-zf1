<?php

class App_Controller_FirstBootController extends Zend_Controller_Action {

    public function init() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $user = new Application_Model_DbTable_User();
            $storage_data = Zend_Auth::getInstance()->getStorage('online-racing')->read();
            $user->set_last_activity($storage_data->id);
        }
    }

}