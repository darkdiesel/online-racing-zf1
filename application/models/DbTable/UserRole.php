<?php

class Application_Model_DbTable_UserRole extends Zend_Db_Table_Abstract {

	protected $_name = 'user_role';
	protected $_primary = 'user_id';
	protected $db_href = 'userrole';

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
				->join(array('r' => 'role'), $this->db_href . '.RoleID = r.ID', array('role_name' => 'r.Name'))
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
