<?php

class Application_Model_DbTable_Role extends Zend_Db_Table_Abstract {

    protected $_name = 'role';
    protected $_primary = 'id';
    protected $db_href = 'rl';

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

        // idencity fields list
        if ($idencity) {
            if (is_array($idencity)) {
                foreach ($idencity as $field => $value) {
                    if ($idencity_data) {
                        if (isset($value['condition'])) {
                            if ($value['condition']) {
                                $condition = $value['condition'];
                            } else {
                                $condition = "OR";
                            }
                        } else {
                            $condition = "OR";
                        }

                        $idencity_data .= sprintf(" %s %s.%s = '%s'", $condition, $this->db_href, $field, $value['value']);
                    } else {
                        $idencity_data = sprintf("%s.%s = '%s'", $this->db_href, $field, $value['value']);
                    }
                }
            } elseif (is_int($idencity) || is_string($idencity)) {
                $idencity_data = sprintf("%s.id = %s", $this->db_href, $idencity);
            }
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
                ->from(array($this->db_href => $this->_name));

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
