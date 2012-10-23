<?php
class CreateModTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = "  CREATE TABLE IF NOT EXISTS `mod` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `game_id` int(11) NOT NULL,
                  `name` text NOT NULL,
                  `developer` text NOT NULL,
                  `year` year(4) NOT NULL,
                  `description` text NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `game_id` (`game_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

                  ALTER TABLE `mod`
                  ADD CONSTRAINT `mod_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`);
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `mod`;";
        $this->_db->query($sql);
    }
}