<?php

class Application_Model_DbTable_Comment extends Zend_Db_Table_Abstract {

    protected $_name = 'comment';
    protected $_primary = 'id';
    protected $db_href = 'coment';

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
