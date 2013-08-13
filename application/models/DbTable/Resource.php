<?php

class Application_Model_DbTable_Resource extends Zend_Db_Table_Abstract
{

    protected $_name = 'Resource';
    protected $_primary = 'id';

    public function getResource($id)
    {
	$model = new self;

	$select = $model->select()
		->setIntegrityCheck(false)
		->from(array('r' => $this->_name), 'r.id')
		->where('r.id = ' . $id)
		->columns('*');

	$resource = $model->fetchRow($select);

	if (count($resource) != 0) {
	    return $resource;
	} else {
	    return FALSE;
	}
    }

}