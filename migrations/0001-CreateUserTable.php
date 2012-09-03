<?php
class CreateUserTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
	
		$sql = " CREATE TABLE IF NOT EXISTS `user` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `login` text NOT NULL,
				  `password` text NOT NULL,
				  `mail` text NOT NULL,
				  `name` text NOT NULL,
				  `surname` text NOT NULL,
				  `country` text NOT NULL,
				  `city` text NOT NULL,
				  `birthday` date NOT NULL,
				  `skype` text NOT NULL,
				  `icq` text NOT NULL,
				  `www` text NOT NULL,
				  `about` text NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
				";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `user`;";
        $this->_db->query($sql);
    }
}