<?php
class CreateUserRoleTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = " CREATE TABLE IF NOT EXISTS `user_role` (
                    `user_role_id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    PRIMARY KEY (`user_role_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;
                  
                  INSERT INTO `user_role` (`user_role_id`, `name`) VALUES
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