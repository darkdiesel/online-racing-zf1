<?php
class CreateFlagTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = " 
                CREATE TABLE IF NOT EXISTS `flag` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(3) NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
                  
                INSERT INTO `flag` (`id`, `name`) VALUES
                (0, '???');
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `role`;";
        $this->_db->query($sql);
    }
}