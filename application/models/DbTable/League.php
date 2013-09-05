<?php

class Application_Model_DbTable_League extends Zend_Db_Table_Abstract
{

    protected $_name = 'league';
    protected $_primary = 'id';

    public function getLeaguePager($count, $page, $page_range, $order)
    {
	$model = new self;

	$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
			->select()
			->setIntegrityCheck(false)
			->from(array('l' => $this->_name), 'l.id')
			->columns(array('l.id', 'l.name', 'l.url_logo', 'l.description', 'l.date_create', 'l.date_edit', 'l.user_id'))
			->join(array('u' => 'user'), 'l.user_id = u.id', array('user_login' => 'u.login'))
			->order('id ' . $order));

	$paginator = new Zend_Paginator($adapter);
	$paginator->setItemCountPerPage($count);
	$paginator->setCurrentPageNumber($page);
	$paginator->setPageRange($page_range);

	return $paginator;
    }

    public function getLeaguesName($order)
    {
	$model = new self;

	$select = $model->select()
		->from($this->_name, 'name')
		->columns(array('id', 'name'))
		->order('name ' . $order);

	$leagues = $model->fetchAll($select);

	if (count($leagues) != 0) {
	    return $leagues;
	} else {
	    return FALSE;
	}
    }

    public function getLeagueData($id)
    {
	$model = new self;

	$select = $model->select()
		->setIntegrityCheck(false)
		->from(array('l' => $this->_name), 'l.id')
		->where('l.id = ?', $id)
		->join(array('u' => 'user'), 'l.user_id = u.id', array('user_login' => 'u.login'))
		->columns('*');

	$league = $model->fetchRow($select);

	if (count($league) != 0) {
	    return $league;
	} else {
	    return FALSE;
	}
    }

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
		->from(array('l' => $this->_name), 'l.id')
		->where('l.' . $idencity_field . ' = ' . $idencity_value)
		->join(array('u' => 'user'), 'l.user_id = u.id', array('user_login' => 'u.login'))
		->columns($fields);

	$resource = $model->fetchRow($select);

	if (count($resource) != 0) {
	    return $resource;
	} else {
	    return FALSE;
	}
    }

}