<?php

class CreateChampionshipTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `championship` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(255) NOT NULL,
                    `url_logo` varchar(255) NOT NULL,
                    `league_id` int(11) NOT NULL,
                    `article_id` int(11) NOT NULL,
                    `game_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `date_start` date NOT NULL,
                    `date_end` date NOT NULL,
                    `description` varchar(500) default NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY  (`id`),
                    KEY `league_id` (`league_id`),
                    KEY `article_id` (`article_id`),
                    KEY `game_id` (`game_id`),
                    KEY `user_id` (`user_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                ALTER TABLE `championship`
                  ADD CONSTRAINT `championship_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `league` (`id`),
                  ADD CONSTRAINT `championship_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`),
                  ADD CONSTRAINT `championship_ibfk_3` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`),
                  ADD CONSTRAINT `championship_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
				";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `championship`;";
        $this->_db->query($sql);
    }

}