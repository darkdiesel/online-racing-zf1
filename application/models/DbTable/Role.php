<?php

class Application_Model_DbTable_Role extends Zend_Db_Table_Abstract
{

    protected $_name = 'role';
    protected $_primary = 'id';

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
		->from(array('r' => $this->_name))
		->where('r.' . $idencity_field . ' = ' . $idencity_value)
		->columns($fields);

	$resource = $model->fetchRow($select);

	if (count($resource) != 0) {
	    return $resource;
	} else {
	    return FALSE;
	}
    }

    /*
     * Function returns array of Items with $fields array of fields list.
     * Sorted by $order value
     * 
     * If $pager == TRUE function return Pager with $pager_args parameters
     * 
     * Parameters:
     * $pager_args['page_count_items']	- Count items for page
     * $pager_args['page']		- Number of curent page
     * $pager_args['page_range']	- Range of pages displaying at the pager's block
     * 
     */

    public function getAll($fields = array(), $order = "ASC", $pager = TRUE, array $pager_args = array())
    {
	$model = new self;

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
		->from(array('r' => $this->_name), 'r.id')
		->columns($fields)
		->order('r.' . $order_field . " " . $order_value);

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
