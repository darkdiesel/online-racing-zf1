<?php

class CreateUserChatTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `user_chat` (
                    `user_chat_msg_id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `message` varchar(500) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY (`user_chat_id`),
                    KEY `user_id` (`user_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                 ALTER TABLE `user_chat`
                    ADD CONSTRAINT `user_chat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);
				";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `user_chat`;";
        $this->_db->query($sql);
    }

}