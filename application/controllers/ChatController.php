<?php

class ChatController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }

    public function addmessageAction()
    {
        $request = $this->getRequest();
        
        if ($this->getRequest()->isPost()) {
            echo $request->getParam('action');
        }
    }


}



