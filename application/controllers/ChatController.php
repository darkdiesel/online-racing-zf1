<?php

class ChatController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        // action body
    }

    public function addmessageAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->layout->disableLayout();
            $request = $this->getRequest();

            if ($this->getRequest()->isPost()) {
                if (($request->getParam('action') == 'addmessage') && ($request->getParam('message_text') != '')) {
                    $message = new Application_Model_UserChat(array('message' => $request->getParam('message_text'),
                                'user_id' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id));
                    $mapper = new Application_Model_UserChatMapper();
                    $mapper->savemessage($message);
                }
            }
        }
    }
    
    public function getmessagesAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        
        if ($this->getRequest()->isPost()) {
            if ($request->getParam('action') == 'getchatmessage'){
                $messages = new Application_Model_UserChatMapper();
                $this->view->chat_messages = $messages->fetchAll();
            }
        }
    }

}