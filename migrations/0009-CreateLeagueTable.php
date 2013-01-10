<?php

class CreateLeagueTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `league` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(120) NOT NULL,
                    `logo` varchar(255) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY  (`id`),
                    KEY `user_id` (`user_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                ALTER TABLE `league`
                    ADD CONSTRAINT `league_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
				";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `league`;";
        $this->_db->query($sql);
    }

}