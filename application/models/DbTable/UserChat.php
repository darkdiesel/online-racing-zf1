<?php

class Application_Model_DbTable_UserChat extends Zend_Db_Table_Abstract {

    protected $_name = 'user_chat';
    protected $_primary = 'id';

    public function fetchLastMsg($last_id) {
        $model = new self;

        $select = $model
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('uc' => 'user_chat'), 'uc.id')
                ->columns(array('uc.id', 'uc.user_id', 'uc.message', 'uc.date_create'))
                ->where('uc.id > ?', $last_id)
                ->join(array('u' => 'user'), 'uc.user_id = u.ID', array('user_login' => 'NickName'))
                ->order('date_create DESC');

        $user_chat_messages = $model->fetchAll($select);

        if (0 == count($user_chat_messages)) {
            return FALSE;
        } else {
            return $user_chat_messages;
        }
    }

    public function fetchUserChatMsg() {
        $model = new self;

        $select = $model
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('uc' => 'user_chat'), 'id')
                ->columns('*')
                ->join(array('u' => 'user'), 'uc.user_id = u.ID', array('user_login' => 'NickName'))
                ->order('date_create DESC')
                ->limit(50, 0);

        $user_chat_messages = $model->fetchAll($select);

        return $user_chat_messages;
    }

}