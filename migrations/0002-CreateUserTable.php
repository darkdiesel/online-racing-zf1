<?php
class CreateUserTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
	
		$sql = " CCREATE TABLE IF NOT EXISTS `user` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `login` varchar(30) NOT NULL,
                            `password` varchar(40) NOT NULL,
                            `last_login` datetime NOT NULL,
                            `activate` varchar(15) NOT NULL,
                            `enabled` int(1) NOT NULL,
                            `role_id` int(11) NOT NULL,
                            `email` varchar(100) NOT NULL,
                            `name` varchar(250) NOT NULL,
                            `surname` varchar(250) NOT NULL,
                            `country` varchar(250) NOT NULL,
                            `lang` varchar(3) NOT NULL,
                            `city` varchar(250) NOT NULL,
                            `birthday` date NOT NULL,
                            `gravatar` varchar(100) NOT NULL,
                            `skype` varchar(255) NOT NULL,
                            `icq` int(20) NOT NULL,
                            `www` varchar(255) NOT NULL,
                            `vk` varchar(255) NOT NULL,
                            `fb` varchar(255) NOT NULL,
                            `tw` varchar(255) NOT NULL,
                            `gp` varchar(255) NOT NULL,
                            `created` datetime NOT NULL,
                            `about` text NOT NULL,
                            PRIMARY KEY (`id`),
                            KEY `role_id` (`role_id`)
                          ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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