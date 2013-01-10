<?php
class CreateUserRoleTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = " CREATE TABLE IF NOT EXISTS `user_role` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(25) NOT NULL,
                    `description` varchar(500) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY  (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                  INSERT INTO `user_role` (`id`, `name`, `description`, `date_create`, `date_edit`) VALUES
                    (1, 'master', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
                    (2, 'admin', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
                    (3, 'user', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
                    (4, 'guest', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `user_role`;";
        $this->_db->query($sql);
    }
}