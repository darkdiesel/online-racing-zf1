<?php

class CreateArticleTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `article` (
                    `article_id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `article_type_id` int(11) NOT NULL,
                    `content_type_id` int(11) NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `text` varchar(10000) NOT NULL,
                    `image` varchar(255) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    `views` int(11) NOT NULL,
                    `publish` int(1) NOT NULL,
                    `last_ip` varchar(50) NOT NULL,
                    PRIMARY KEY (`article_id`),
                    KEY `user_id` (`user_id`),
                    KEY `type_id` (`article_type_id`),
                    KEY `content_type_id` (`content_type_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                  ALTER TABLE `article`
                    ADD CONSTRAINT `article_ibfk_3` FOREIGN KEY (`content_type_id`) REFERENCES `content_type` (`content_type_id`),
                    ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
                    ADD CONSTRAINT `article_ibfk_2` FOREIGN KEY (`article_type_id`) REFERENCES `article_type` (`article_type_id`);
                 
				";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `article`;";
        $this->_db->query($sql);
    }

}