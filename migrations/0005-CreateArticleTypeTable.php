<?php

class CreateArticleTypeTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `article_type` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(100) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY  (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;
                  
                INSERT INTO `article_type` (`id`, `name`, `description`, `date_create`, `date_edit`) VALUES
                    (1, 'news', 'Тип статьи для создания новосного контента на сайте.', '0000-00-00 00:00:00', '2012-12-06 13:17:57'),
                    (2, 'plugins', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
                    (3, 'game', 'Тип статьи для добавления на сайт игр.', '2012-12-11 19:08:39', '2012-12-11 19:08:39'),
                    (4, 'rule', 'Тип статьи используется для добавления регламентов на сайт  и использования их в качестве сборника законов и указаний для гонщиков и организаторов.', '2012-12-27 12:42:50', '2012-12-27 12:42:50');
                ";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `article_type`;";
        $this->_db->query($sql);
    }

}