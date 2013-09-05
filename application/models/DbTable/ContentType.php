<?php

class Application_Model_DbTable_ContentType extends Zend_Db_Table_Abstract
{

    protected $_name = 'content_type';
    protected $_primary = 'id';

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
	
	if (!isset($idencity_field) || !isset($idencity_value))
	    return FALSE;

	if (!count($fields) || strtolower($fields) == 'all')
	    $fields = "*";
	else {
	    if (is_array($fields)) {
		$fields = array_map('trim', $fields);
	    } elseif (is_string($fields)) {
		$fields = array_map('trim', explode(",", $fields));
	    }
	}

	$select = $model->select()
		->setIntegrityCheck(false)
		->from(array('ct' => $this->_name), 'ct.id')
		->where('ct.' . $idencity_field . ' = ' . $idencity_value)
		->columns($fields);

	$resource = $model->fetchRow($select);

	if (count($resource) != 0) {
	    return $resource;
	} else {
	    return FALSE;
	}
    }

    public function getAll($fields = array(), $order = "ASC", $pager = 0, array $pager_args = array())
    {
	$model = new self;

	if (!count($fields) || strtolower($fields) == 'all')
	    $fields = "*";
	else {
	    if (is_array($fields)) {
		$fields = array_map('trim', $fields);
	    } elseif (is_string($fields)) {
		$fields = array_map('trim', explode(",", $fields));
	    }
	}

	if (!count($order)) {
	    $order_field = 'id';
	    $order_value = "ASC";
	} elseif (is_array($order)) {
	    $order_field = $order[0];
	    $order_value = $order[1];
	} elseif (is_string($order)) {
	    $order_field = 'id';
	    $order_value = $order;
	}

	$select = $model->select()
		->from(array('ct' => $this->_name), 'ct.id')
		->columns($fields)
		->order('ct.' . $order_field . " " . $order_value);

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