<?php

class Application_Model_DbTable_Championship extends Zend_Db_Table_Abstract {

	protected $_name = 'championship';
	protected $_primary = 'id';

	public function getChampionshipData($league_id, $championship_id) {
		$model = new self;

		$select = $model
				->select()
				->setIntegrityCheck(false)
				->from(array('c' => $this->_name), 'c.id')
				->where("c.league_id = {$league_id} and c.id = {$championship_id}")
				->join(array('l' => 'league'), 'c.league_id = l.id', array('league_name' => 'l.name'))
				->join(array('p1' => 'post'), 'c.rule_id = p1.id', array('rule_name' => 'p1.name'))
				->join(array('p2' => 'post'), 'c.game_id = p2.id', array('game_name' => 'p2.name'))
				->join(array('u' => 'user'), 'c.user_id = u.id', array('user_login' => 'u.login'))
				->columns('*');

		$championship = $model->fetchRow($select);

		if (count($championship) != 0) {
			return $championship;
		} else {
			return FALSE;
		}
	}

	public function getChampionshipsPagerByLeague($count, $page, $page_range, $order, $league_id) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->setIntegrityCheck(false)
						->from(array('c' => $this->_name), 'c.id')
						->where('c.league_id = ?', $league_id)
						->join(array('l' => 'league'), 'c.league_id = l.id', array('league_name' => 'l.name'))
						->join(array('p1' => 'post'), 'c.rule_id = p1.id', array('rule_name' => 'p1.name'))
						->join(array('p2' => 'post'), 'c.game_id = p2.id', array('game_name' => 'p2.name'))
						->join(array('u' => 'user'), 'c.user_id = u.id', array('user_login' => 'u.login'))
						->columns('*')
						->order('c.id ' . $order)
		);

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

	public function checkExistChampionshipName($championship_name) {
		$model = new self;
		$select = $model->select()
				->from($this->_name, 'id')
				->where('name = ?', $championship_name)
				->columns('id');

		$championship_data = $model->fetchRow($select);

		if (count($championship_data) != 0) {
			return $championship_data->id;
		} else {
			return FALSE;
		}
	}

	public function checkExistChampionshipById($id) {
		$model = new self;
		$select = $model->select()
				->from($this->_name, 'id')
				->where('id = ?', $id)
				->columns('id');

		$championship_data = $model->fetchRow($select);

		if (count($championship_data) != 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getChampionshipNameById($id) {
		$model = new self;
		$select = $model->select()
				->from($this->_name)
				->where('id = ?', $id)
				->columns('name');

		$championship_data = $model->fetchRow($select);

		if (count($championship_data) != 0) {
			return $championship_data;
		} else {
			return FALSE;
		}
	}

}
