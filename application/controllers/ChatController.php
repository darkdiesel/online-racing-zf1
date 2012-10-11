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
                if (($request->getParam('ajax_action') == 'add_message') && ($request->getParam('message_text') != '')) {
                    $message_text = htmlspecialchars((trim($request->getParam('message_text'))));
                    $message = new Application_Model_UserChat(array('message' => $message_text,
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
            if ($request->getParam('ajax_action') == 'get_chat_messages'){
                $messages = new Application_Model_UserChatMapper();
                $chat_messages = $messages->fetchAll();
                
                $messages_html = '';
                $bbcode = Zend_Markup::factory('Bbcode');
                
                foreach ($chat_messages as $message):
                    // get user login
                    $mapper = new Application_Model_UserMapper();
                    $user_login = $mapper->getUserLoginById($message->user_id);
                    
                    //construct message html code
                    $messages_html .= '<div class="chat_message_box">';
                    $messages_html .= '<div class="chat_mesage_date">'.$message->date.'</div>';
                    $messages_html .= '<div class="chat_mesage_nickname">';
                    $messages_html .= '<a href="'.'user/info/'.$message->user_id.'" target="_BLINK"><i class="icon-user icon-black"></i></a>';
                    $messages_html .= '<a href="javascript:void('."'Apply to'".')" class="nick" onClick="$('."'#chat #userChat #messageTextArea').val($('#chat #userChat #messageTextArea').val() + '[i]'+$(this).html()+'[/i], '); $('#chat #userChat #messageTextArea').focus()".'">'.$user_login->login.'</a>';
                    $messages_html .= '</div>';
                    $messages_html .= '<div class="chat_mesage_message">'.$bbcode->render($message->message).'</div>';
                    $messages_html .= '</div>';
                endforeach;
                    
                $data_str = array('message_html' => $messages_html);
                echo json_encode($data_str);
            }
        }
    }

}