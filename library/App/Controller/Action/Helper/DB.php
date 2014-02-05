<?php

/*
 * Controller Action Helper for creating DB classes for resources
 */

class App_Controller_Action_Helper_DB extends Zend_Controller_Action_Helper_Abstract {

	protected $db;

	public function __construct() {
		$this->db = array(
			'user' => '',
			'resource' => '',
			'role' => '',
			'user_role' => '',
			'resource_access' => '',
			'content_type' => '',
			'post' => '',
			'post_type' => '',
			'country' => '',
			'privilege' => '',
			'team' => '',
			'track' => '',
			'championship_race' => '',
			'league' => '',
		);
	}

	protected function getDB($db_name) {
		return $this->db[$db_name];
	}

	protected function createDB($db_name) {
		switch ($db_name) {
			case "user":
				$this->db[$db_name] = new Application_Model_DbTable_User();
				break;
			case "resource":
				$this->db[$db_name] = new Application_Model_DbTable_Resource();
				break;
			case "role":
				$this->db[$db_name] = new Application_Model_DbTable_Role();
				break;
			case "user_role":
				$this->db[$db_name] = new Application_Model_DbTable_UserRole();
				break;
			case "resource_access":
				$this->db[$db_name] = new Application_Model_DbTable_ResourceAccess();
				break;
			case "content_type":
				$this->db[$db_name] = new Application_Model_DbTable_ContentType();
				break;
			case "post":
				$this->db[$db_name] = new Application_Model_DbTable_Post();
				break;
			case "post_type":
				$this->db[$db_name] = new Application_Model_DbTable_PostType();
				break;
			case "country":
				$this->db[$db_name] = new Application_Model_DbTable_Country();
				break;
			case "privilege":
				$this->db[$db_name] = new Application_Model_DbTable_Privilege();
				break;
			case "team":
				$this->db[$db_name] = new Application_Model_DbTable_Team();
				break;
			case "track":
				$this->db[$db_name] = new Application_Model_DbTable_Track();
				break;
			case "championship_race":
				$this->db[$db_name] = new Application_Model_DbTable_ChampionshipRace;
				break;
			case "league":
				$this->db[$db_name] = new Application_Model_DbTable_League();
				break;
			default:
				return FALSE;
		}
	}

	protected function existDB($db_name) {
		if ($this->db[$db_name]) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function get($db_name) {
		$db_name = strtolower($db_name);
		if ($this->existDB(strtolower($db_name))) {
			return $this->getDB($db_name);
		} else {
			$this->createDB($db_name);
			return $this->getDB($db_name);
		}
	}

	public function getIdencity($idencity = array(), $db_href) {
		$idencity_data = "";
		$field_idencity = "";
		
		if (is_array($idencity)) {
			// if idencity - array
			foreach ($idencity as $field => $conditions) {
				if (is_array($conditions)) {
					if (isset($conditions['value']) || isset($conditions['condition']) || isset($conditions['sign'])) {
						//get value
							$value = $conditions['value'];

							//get sign
							if (isset($conditions['sign'])) {
								$sign = $conditions['sign'];
							} else {
								$sign = '=';
							}

							//get compare condition
							if (isset($conditions['condition'])) {
								$condition = $conditions['condition'];
							} else {
								$condition = "OR";
							}
					} else {
						$field_idencity = "";
						// if conitions is array
						foreach ($conditions as $condition) {
							//get value
							$value = $condition['value'];

							//get sign
							if (isset($condition['sign'])) {
								$sign = $condition['sign'];
							} else {
								$sign = '=';
							}

							//get compare condition
							if (isset($condition['condition'])) {
								$condition = $condition['condition'];
							} else {
								$condition = "OR";
							}

							if ($field_idencity) {
								$field_idencity .= sprintf(" %s %s.%s %s '%s'", $condition, $db_href, $field, $sign, $value);
							} else {
								$field_idencity = sprintf("%s.%s %s '%s'", $db_href, $field, $sign, $value);
							}
						}
					}
				} else {
					$condition = "OR";
					$sign = "=";
					$value = $conditions;
				}

				if ($idencity_data) {
					if ($field_idencity) {
						$idencity_data .= " (" . $field_idencity . ")";
					} else {
						$idencity_data .= sprintf(" %s %s.%s %s '%s'", $condition, $db_href, $field, $sign, $value);
					}
				} else {
					if ($field_idencity) {
						$idencity_data = "(" . $field_idencity . ")";
					} else {
						$idencity_data = sprintf("%s.%s %s '%s'", $db_href, $field, $sign, $value);
					}
				}
			}
		} elseif (is_int($idencity) || is_string($idencity)) {
			$idencity_data = sprintf("%s.id = %s", $db_href, $idencity);
		}

		return $idencity_data;
	}

}
