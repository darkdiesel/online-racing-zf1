<?php

class CreateArticleTypeTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `article_type` (
                    `article_type_id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY (`article_type_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                ";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `article_type`;";
        $this->_db->query($sql);
    }

}