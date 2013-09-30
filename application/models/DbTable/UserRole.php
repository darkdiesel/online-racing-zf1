<?php

class Application_Model_DbTable_UserRole extends Zend_Db_Table_Abstract
{

    protected $_name = 'user_role';
    protected $_primary = 'user_id';

    /*
     * Get Item by idencity field value and $field array of fields list.
     */

    public function getItem($idencity = array(), $fields = array())
    {
	$model = new self;

	if (!count($idencity)) {
	    return FALSE;
	} elseif (is_array($idencity)) {
	    $idencity_field = $idencity[0];
	    $idencity_value = $idencity[1];
	} elseif (is_int($idencity)) {
	    $idencity_field = 'id';
	    $idencity_value = $idencity;
	}

	if (!isset($idencity_field) || !isset($idencity_value)) {
	    return FALSE;
	}

	if (is_array($fields)) {
	    if (count($fields)) {
		$fields = array_map('trim', $fields);
	    } else {
		$fields = "*";
	    }
	} elseif (is_string($fields)) {
	    if (strtolower($fields) == "all") {
		$fields = "*";
	    } else {
		$fields = array_map('trim', explode(",", $fields));
	    }
	}

	$select = $model->select()
		->setIntegrityCheck(false)
		->from(array('ur' => $this->_name))
		->join(array('r' => 'role'), 'ur.role_id = r.id',array('role_name' => 'r.name'))
		->where('ur.' . $idencity_field . ' = ' . $idencity_value)
		->columns($fields);

	$resource = $model->fetchRow($select);

	if (count($resource) != 0) {
	    return $resource;
	} else {
	    return FALSE;
	}
    }

}
