<?php

class App_Controller_Action_Helper_MessageManager extends Zend_Controller_Action_Helper_Abstract {

    protected $session;

    public function __construct(){
	$this->session = new Zend_Session_Namespace('App_Messages');
    }

    protected function addMessage($message, $type) {
        $messages = (isset($this->session->messages)) ? $this->session->messages : array();
	
        if (array_key_exists($type, $messages)) {
            $messages [$type] [] = $message;
        } else {
            $messages [$type] = array(
                $message
            );
        }
	
	if (!empty($messages)){
	    $this->session->messages = $messages;
	}
    }

    public function addError($message) {
        $this->addMessage($message, 'error');
    }

    public function addSuccess($message) {
        $this->addMessage($message, 'success');
    }

    public function addInfo($message) {
        $this->addMessage($message, 'info');
    }

    public function addWarning($message) {
        $this->addMessage($message, 'warning');
    }
    
    public function clearMessages(){
	$this->session->messages = "";
    }

}