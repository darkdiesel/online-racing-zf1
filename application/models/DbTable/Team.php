<?php

class Application_Model_DbTable_Team extends Zend_Db_Table_Abstract {

	protected $_name = 'team';
	protected $_primary = 'id';
	protected $db_href = 'team';

	public function getName($team_id) {
		$model = new self;
		$select = $model->select()
				->from(array('t' => $this->_name), 'id')
				->where('t.id = ?', $team_id)
				->columns(array('name'));
		$team = $model->fetchRow($select);

		if (count($team) != 0) {
			return $team->name;
		} else {
			return FALSE;
		}
	}

	public function getId($team_name) {
		$model = new self;
		$select = $model->select()
				->from(array('t' => $this->_name), 'name')
				->where('t.name = ?', $team_name)
				->columns(array('id'));
		$team = $model->fetchRow($select);

		if (count($team) != 0) {
			return $team->id;
		} else {
			return FALSE;
		}
	}

	public function getTeamsPager($count, $page, $page_range, $order) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->from('team', 'id')
						->columns(array('id', 'name', 'description', 'date_create', 'date_edit'))
						->order('id ' . $order));

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

	public function getTeamNames($order) {
		$model = new self;

		$select = $model->select()
				->from($this->_name, 'name')
				->columns(array('id', 'name'))
				->order('name ' . $order);

		$teams = $model->fetchAll($select);

		if (count($teams) != 0) {
			return $teams;
		} else {
			return FALSE;
		}
	}

	/*
	 * Get Item by idencity field value and $field array of fields list.
	 */

	public function getItem($idencity = array(), $fields = array()) {
		$model = new self;
		$idencity_data = "";

		// idencity fields list
		if (!count($idencity)) {
			return FALSE;
		} elseif (is_array($idencity)) {
			foreach ($idencity as $field => $value) {
				if (is_array($value)) {
					if (isset($value['condition'])) {
						if ($value['condition']) {
							$condition = $value['condition'];
						} else {
							$condition = "OR";
						}
					} else {
						$condition = "OR";
					}
					$value = $value['value'];
				} else {
					$condition = "OR";
				}

				if ($idencity_data) {
					$idencity_data .= sprintf(" %s %s.%s = '%s'", $condition, $this->db_href, $field, $value);
				} else {
					$idencity_data = sprintf("%s.%s = '%s'", $this->db_href, $field, $value);
				}
			}
		} elseif (is_int($idencity) || is_string($idencity)) {
			$idencity_data = sprintf("%s.id = '%s'", $this->db_href, $idencity);
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

}
