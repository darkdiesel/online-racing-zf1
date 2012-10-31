<?php

class CreateArticleTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `article` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `text` varchar(10000) NOT NULL,
                    `date` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    `views` int(11) NOT NULL,
                    `last_ip` varchar(50) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                 ALTER TABLE `article`
                     ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
				";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `article`;";
        $this->_db->query($sql);
    }

}