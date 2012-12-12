<?php

class CreateContentTypeTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `content_type` (
                    `content_type_id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY (`content_type_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                ";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `content_type`;";
        $this->_db->query($sql);
    }

}