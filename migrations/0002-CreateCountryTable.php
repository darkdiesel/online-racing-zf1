<?php
class CreateCountryTable extends Akrabat_Db_Schema_AbstractChange
{
    function up()
    {
        $sql = " 
                CREATE TABLE IF NOT EXISTS `country` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(255) NOT NULL,
                    `abbreviation` varchar(5) NOT NULL,
                    `ImageRoundUrl` varchar(255) NOT NULL,
                    `ImageGlossyWaveUrl` varchar(255) NOT NULL,
                    `date_create` datetime NOT NULL,
                    `date_edit` datetime NOT NULL,
                    PRIMARY KEY  (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;
                  
                INSERT INTO `country` (`id`, `name`, `abbreviation`, `ImageRoundUrl`, `ImageGlossyWaveUrl`, `date_create`, `date_edit`) VALUES
                    (1, 'Europe', 'EUR', '/img/data/flags/eur_image_round.png', '/img/data/flags/eur_image_glossy_wave.png', '2012-12-21 18:47:58', '2012-12-21 18:47:58');
               ";
        $this->_db->query($sql);
    }

    function down()
    {
        $sql = "DROP TABLE IF EXISTS `flag`;";
        $this->_db->query($sql);
    }
}