<?php

class Application_Model_DbTable_Resource extends Zend_Db_Table_Abstract
{

    protected $_name = 'Resource';
    protected $_primary = 'id';

    public function getItem($id, $fields = array())
    {
	$model = new self;

	if (!count($fields) || strtolower($fields) == 'all')
	    $fields = "*";
	else {
	    $fields = array_map('trim', explode(",", $fields));
	}

	$select = $model->select()
		->setIntegrityCheck(false)
		->from(array('r' => $this->_name), 'r.id')
		->where('r.id = ' . $id)
		->columns($fields);

	$resource = $model->fetchRow($select);

	if (count($resource) != 0) {
	    return $resource;
	} else {
	    return FALSE;
	}
    }

    public function getAll($fields = "", $order = "ASC", $pager = 0, array $pager_args = array())
    {
	$model = new self;

	if (!count($fields) || strtolower($fields) == 'all')
	    $fields = "*";
	else {
	    $fields = array_map('trim', explode(",", $fields));
	}

	$select = $model->select()
		->from(array('r' => $this->_name), 'r.id')
		->columns($fields)
		->order('r.id' . " " . $order);

	if ($pager) {
	    $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);

	    $paginator = new Zend_Paginator($adapter);
	    if (count($pager_args)) {
		$paginator->setItemCountPerPage($pager_args['page_count_items']);
		$paginator->setCurrentPageNumber($pager_args['page']);
		$paginator->setPageRange($pager_args['page_range']);
	    } else {
		$paginator->setItemCountPerPage("10");
		$paginator->setCurrentPageNumber("1");
		$paginator->setPageRange("5");
	    }

	    return $paginator;
	} else {
	    $resources = $model->fetchAll($select);

	    if (count($resources) != 0) {
		return $resources;
	    } else {
		return FALSE;
	    }
	}
    }

}