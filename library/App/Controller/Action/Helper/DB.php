<?php

/*
 * Controller Action Helper for creating DB classes for resources
 */

class App_Controller_Action_Helper_DB extends Zend_Controller_Action_Helper_Abstract
{

    protected $db;
    
    public function __construct()
    {
	$this->db = array(
	  'user' => '',
	  'resource' => '',
	  'role' => '',
	  'user_role' => '',
	);
    }
    
    protected function getDB($db_name)
    {
	return $this->db[$db_name];
    }

    protected function createDB($db_name)
    {
	switch ($db_name) {
	    case "user":
		$this->db[$db_name] = new Application_Model_DbTable_User();
		break;
	    case "resource":
		$this->db[$db_name] = new Application_Model_DbTable_Resource();
		break;
	    case "role":
		$this->db[$db_name] = new Application_Model_DbTable_Role();
		break;
	    case "user_role":
		$this->db[$db_name] = new Application_Model_DbTable_UserRole();
		break;
	    default:
		return FALSE;
	}
    }
    
    protected function existDB($db_name)
    {
	if ($this->db[$db_name]){
	    return TRUE;
	} else {
	    return FALSE;
	}
    }

    public function get($db_name){
	$db_name = strtolower($db_name);
	if ($this->existDB(strtolower($db_name))){
	    return $this->getDB($db_name);
	} else{
	    $this->createDB($db_name);
	    return $this->getDB($db_name);
	}
    }

}
