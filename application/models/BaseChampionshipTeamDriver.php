<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('Model_ChampionshipTeamDriver', 'doctrine');

/**
 * Model_BaseChampionshipTeamDriver
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $championship_id
 * @property integer $team_id
 * @property integer $user_id
 * @property integer $team_role_id
 * @property integer $driver_number
 * @property timestamp $date_create
 * @property timestamp $date_edit
 * @property Model_Championship $Championship
 * @property Model_Team $Team
 * @property Model_User $User
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class Model_BaseChampionshipTeamDriver extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('championship_team_driver');
        $this->hasColumn('championship_id', 'integer', 4, array(
             'type' => 'integer',
             'length' => 4,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('team_id', 'integer', 4, array(
             'type' => 'integer',
             'length' => 4,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('user_id', 'integer', 4, array(
             'type' => 'integer',
             'length' => 4,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('team_role_id', 'integer', 4, array(
             'type' => 'integer',
             'length' => 4,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('driver_number', 'integer', 4, array(
             'type' => 'integer',
             'length' => 4,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('date_create', 'timestamp', null, array(
             'type' => 'timestamp',
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('date_edit', 'timestamp', null, array(
             'type' => 'timestamp',
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('Model_Championship as Championship', array(
             'local' => 'championship_id',
             'foreign' => 'id'));

        $this->hasOne('Model_Team as Team', array(
             'local' => 'team_id',
             'foreign' => 'id'));

        $this->hasOne('Model_User as User', array(
             'local' => 'user_id',
             'foreign' => 'id'));
    }
}