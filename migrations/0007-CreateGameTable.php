<?php
class CreateGameTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = "  CREATE TABLE IF NOT EXISTS `game` (
                    `game_id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `article_id` int(11) NOT NULL,
                    PRIMARY KEY (`game_id`),
                    KEY `article_id` (`article_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                   ALTER TABLE `game`
                    ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`article_id`);
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `game`;";
        $this->_db->query($sql);
    }
}