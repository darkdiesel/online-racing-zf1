<?php
class CreateGameModTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = "  CREATE TABLE IF NOT EXISTS `game_mod` (
                    `game_mod_id` int(11) NOT NULL AUTO_INCREMENT,
                    `game_id` int(11) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `article_id` int(11) NOT NULL,
                    PRIMARY KEY (`game_mod_id`),
                    KEY `game_id` (`game_id`),
                    KEY `article_id` (`article_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                    ALTER TABLE `game_mod`
                      ADD CONSTRAINT `game_mod_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `article` (`article_id`),
                      ADD CONSTRAINT `game_mod_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`game_id`);
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `game_mod`;";
        $this->_db->query($sql);
    }
}