<?php

class Application_Model_DbTable_Championship extends Zend_Db_Table_Abstract {

	protected $_name = 'championship';
	protected $_primary = 'id';
	protected $db_href = 'champ';

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

	/*
	 * Get Item by idencity field value and $field array of fields list.
	 */

	public function getItem($idencity = array(), $fields = array()) {
		$model = new self;
		$db = new App_Controller_Action_Helper_DB();
		$idencity_data = "";

		// idencity fields list
		if (count($idencity)) {
			$idencity_data = $db->getIdencity($idencity, $this->db_href);
		} else {
			return FALSE;
		}

		// fields list
		if ($fields) {
			if (is_array($fields)) {
				$fields = array_map('trim', $fields);
			} elseif (is_string($fields)) {
				if (strtolower($fields) == "all") {
					$fields = "*";
				} else {
					$fields = array_map('trim', explode(",", $fields));
				}
			}
		}

		$select = $model->select()
				->setIntegrityCheck(false)
				->from(array($this->db_href => $this->_name))
				->join(array('u' => 'user'), $this->db_href . '.user_id = u.id', array('user_login' => 'u.login'))
				->join(array('lg' => 'league'), $this->db_href . '.league_id = u.id', array(
					'legue_login' => 'lg.login',
					'league_url_logo' => 'lg.url_logo',
					'league_description' => 'lg.description',
					'league_user_id' => 'lg.user_id',
					'league_date_create' => 'lg.date_create',
					'league'
					))
				->where($idencity_data);

		if ($fields) {
			$select->columns($fields);
		} else {
			$select->columns("*");
		}

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

	public function getAll($idencity = array(), $fields = array(), $order = "ASC", $pager = FALSE, array $pager_args = array()) {
		$model = new self;
		$idencity_data = "";
		$order_data = "";

		$db = new App_Controller_Action_Helper_DB();

		// idencity fields list
		if ($idencity) {
			$idencity_data = $db->getIdencity($idencity, $this->db_href);
		}

		// fields list
		if ($fields) {
			if (is_array($fields)) {
				$fields = array_map('trim', $fields);
			} elseif (is_string($fields)) {
				if (strtolower($fields) == "all") {
					$fields = "*";
				} else {
					$fields = array_map('trim', explode(",", $fields));
				}
			}
		}

		// order list
		if ($order) {
			if (is_array($order)) {
				foreach ($order as $field => $value) {
					if ($order_data) {
						$order_data .= sprintf(", %s.%s %s", $this->db_href, $field, $value);
					} else {
						$order_data = sprintf("%s.%s %s", $this->db_href, $field, $value);
					}
				}
			} elseif (is_string($order) && !empty($order)) {
				$order_data = sprintf("%s.id %s", $this->db_href, $order);
			}
		}

		$select = $model->select()
				->setIntegrityCheck(false)
				->from(array($this->db_href => $this->_name))
				->join(array('u' => 'user'), $this->db_href . '.user_id = u.id', array('user_login' => 'u.login'));

		if ($fields) {
			$select->columns($fields);
		} else {
			$select->columns("*");
		}

		if ($idencity_data) {
			$select->where($idencity_data);
		}

		if ($order_data) {
			$select->order($order_data);
		}

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

			if (count($paginator) > 0) {
				return $paginator;
			} else {
				return FALSE;
			}
		} else {
			$resources = $model->fetchAll($select);

			if (count($resources) > 0) {
				return $resources;
			} else {
				return FALSE;
			}
		}
	}

}
