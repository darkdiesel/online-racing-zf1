<?php
class CreateGameModTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = "  CREATE TABLE IF NOT EXISTS `game_mod` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `game_id` int(11) NOT NULL,
                    `name` text NOT NULL,
                    `developer` text NOT NULL,
                    `year` year(4) NOT NULL,
                    `description` text NOT NULL,
                    `article_id` int(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `game_id` (`game_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

                  ALTER TABLE `game_mod`
                    ADD CONSTRAINT `game_mod_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`);
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `game_mod`;";
        $this->_db->query($sql);
    }
}