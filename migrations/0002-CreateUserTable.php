<?php
class CreateUserTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
	
		$sql = " CREATE TABLE IF NOT EXISTS `user` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `login` char(30) NOT NULL,
				  `password` char(40) NOT NULL,
				  `activate` text NOT NULL,
				  `enabled` int(1) NOT NULL,
				  `role_id` int(11) NOT NULL,
				  `email` char(100) NOT NULL,
				  `name` char(250) NOT NULL,
				  `surname` char(250) NOT NULL,
				  `country` char(250) NOT NULL,
				  `city` char(250) NOT NULL,
				  `birthday` date NOT NULL,
				  `skype` char(255) NOT NULL,
				  `icq` int(20) NOT NULL,
				  `www` char(255) NOT NULL,
				  `vk` char(255) NOT NULL,
				  `fb` char(255) NOT NULL,
				  `tw` char(255) NOT NULL,
				  `gp` char(255) NOT NULL,
				  `created` datetime NOT NULL,
				  `about` text NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `role_id` (`role_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

				ALTER TABLE `user`
  					ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`);
				";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `user`;";
        $this->_db->query($sql);
    }
}