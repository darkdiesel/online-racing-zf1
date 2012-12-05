<?php

class CreateLeagueTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `league` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(120) NOT NULL,
                    `logo` varchar(255) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `date` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
				";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `league`;";
        $this->_db->query($sql);
    }

}