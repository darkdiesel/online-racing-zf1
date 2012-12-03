<?php
class CreateUserRoleTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = " CREATE TABLE IF NOT EXISTS `user_role` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(25) NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                  
                  INSERT INTO `user_role` (`id`, `name`) VALUES
                    (1, 'master'),
                    (2, 'admin'),
                    (3, 'user'),
                    (4, 'guest');
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `user_role`;";
        $this->_db->query($sql);
    }
}