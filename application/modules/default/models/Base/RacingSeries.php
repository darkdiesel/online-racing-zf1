<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('Default_Model_RacingSeries', 'default');

/**
 * Default_Model_Base_RacingSeries
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $ID
 * @property string $Name
 * @property string $Description
 * @property timestamp $DateCreate
 * @property timestamp $DateEdit
 * @property Doctrine_Collection $Championship
 * @property Doctrine_Collection $Team
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class Default_Model_Base_RacingSeries extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('racing_series');
        $this->hasColumn('ID', 'integer', 4, array(
             'type' => 'integer',
             'length' => 4,
             'fixed' => false,
             'unsigned' => false,
             'primary' => true,
             'autoincrement' => true,
             ));
        $this->hasColumn('Name', 'string', 255, array(
             'type' => 'string',
             'length' => 255,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('Description', 'string', 500, array(
             'type' => 'string',
             'length' => 500,
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('DateCreate', 'timestamp', null, array(
             'type' => 'timestamp',
             'fixed' => false,
             'unsigned' => false,
             'primary' => false,
             'notnull' => true,
             'autoincrement' => false,
             ));
        $this->hasColumn('DateEdit', 'timestamp', null, array(
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
        $this->hasMany('Default_Model_Championship as Championship', array(
             'local' => 'ID',
             'foreign' => 'RacingSeriesID'));

        $this->hasMany('Default_Model_Team as Team', array(
             'local' => 'ID',
             'foreign' => 'RacingSeriesID'));
    }
}