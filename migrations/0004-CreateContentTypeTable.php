<?php

class CreateContentTypeTable extends Akrabat_Db_Schema_AbstractChange {

    function up() {

        $sql = " CREATE TABLE IF NOT EXISTS `content_type` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(255) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY  (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

                 INSERT INTO `content_type` (`id`, `name`, `description`, `date_create`, `date_edit`) VALUES
                    (1, 'text', 'Любые символы введенные в поле будут рассмотрены как текст.', '2012-12-27 12:50:22', '2012-12-27 12:50:22'),
                    (2, 'bbcode', 'Поле содержит текст и разрешимый диапозон тегов, так называемых bbcode, теги вместо привычных треугольных скобок < имя тега > заменяются на квадратные [ имя тега ].', '2012-12-27 12:52:38', '2012-12-27 12:52:38'),
                    (3, 'full html', 'Поле может содержать как текст, так и html код. Будьте внимательны, закрывайте теги, чтобы они не навредили разметке сайта.', '2012-12-27 12:55:28', '2012-12-27 12:55:28');
                ";
        $this->_db->query($sql);
    }

    function down() {
        $sql = "DROP TABLE IF EXISTS `content_type`;";
        $this->_db->query($sql);
    }

}