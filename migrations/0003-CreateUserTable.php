<?php
class CreateUserTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
	
		$sql = " CREATE TABLE IF NOT EXISTS `user` (
                            `id` int(11) NOT NULL auto_increment,
                            `login` varchar(30) NOT NULL,
                            `password` varchar(40) NOT NULL,
                            `date_last_activity` datetime NOT NULL,
                            `code_activate` varchar(15) NOT NULL,
                            `code_restore_pass` varchar(15) NOT NULL,
                            `enable` int(1) NOT NULL,
                            `user_role_id` int(11) NOT NULL,
                            `email` varchar(100) NOT NULL,
                            `name` varchar(250) NOT NULL,
                            `surname` varchar(250) NOT NULL,
                            `country_id` int(11) NOT NULL,
                            `lang` varchar(3) NOT NULL,
                            `city` varchar(100) NOT NULL,
                            `birthday` date NOT NULL,
                            `avatar_type` int(1) NOT NULL,
                            `avatar_load` varchar(255) NOT NULL,
                            `avatar_link` varchar(255) NOT NULL,
                            `avatar_gravatar_email` varchar(255) NOT NULL,
                            `skype` varchar(255) NOT NULL,
                            `icq` varchar(20) NOT NULL,
                            `gtalk` varchar(100) NOT NULL,
                            `www` varchar(255) NOT NULL,
                            `vk` varchar(255) NOT NULL,
                            `fb` varchar(255) NOT NULL,
                            `tw` varchar(255) NOT NULL,
                            `gp` varchar(255) NOT NULL,
                            `date_create` datetime NOT NULL,
                            `date_edit` datetime NOT NULL,
                            `about` varchar(500) NOT NULL,
                            PRIMARY KEY  (`id`),
                            KEY `role_id` (`user_role_id`),
                            KEY `country_id` (`country_id`)
                          ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


                        ALTER TABLE `user`
                            ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`),
                            ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`user_role_id`) REFERENCES `user_role` (`id`);
			";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `user`;";
        $this->_db->query($sql);
    }
}