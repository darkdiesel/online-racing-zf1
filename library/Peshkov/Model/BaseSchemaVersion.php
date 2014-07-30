<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('Peshkov_Model_SchemaVersion', 'doctrine');

/**
 * Peshkov_Model_BaseSchemaVersion
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $version
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class Peshkov_Model_BaseSchemaVersion extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('schema_version');
        $this->hasColumn('version', 'integer', 8, array(
             'type' => 'integer',
             'length' => 8,
             'fixed' => false,
             'unsigned' => false,
             'primary' => true,
             'autoincrement' => false,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        
    }
}