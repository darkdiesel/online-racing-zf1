<?php

class CreateContentTypeTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `content_type` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
                ";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `article_type`;";
        $this->_db->query($sql);
    }

}