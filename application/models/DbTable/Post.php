<?php

class Application_Model_DbTable_Post extends Zend_Db_Table_Abstract {

	protected $_name = 'post';
	protected $_primary = 'id';
	protected $db_href = 'p';

	public function getPublishedPostData($id) {
		$model = new self;

		$select = $model->select()
				->setIntegrityCheck(false)
				->from(array('a' => $this->_name), 'a.id')
				->where('a.id = ?', $id)
				->where('a.publish = 1')
				->join(array('u' => 'user'), 'a.user_id = u.id', array('user_login' => 'u.login'))
				->join(array('a_t' => 'post_type'), 'a_t.id = a.post_type_id', array('post_type_name' => 'a_t.name'))
				->columns('*');

		$post = $model->fetchRow($select);

		if (count($post) != 0) {
			// update count of views
			if ($post->last_ip != $_SERVER['REMOTE_ADDR']) {
				$post_data = array(
					'views' => ($post->views + 1),
					'last_ip' => $_SERVER['REMOTE_ADDR']
				);

				$post_where = $model->getAdapter()->quoteInto('id = ?', $id);
				$model->update($post_data, $post_where);
			}

			return $post;
		} else {
			return FALSE;
		}
	}

	public function getPublishPostTitlesByType($post_type, $order) {
		$model = new self;

		$select = $model->select()
				->from($this->_name, 'id')
				->where('publish=1 and post_type_id=' . $post_type)
				->order('title ' . $order)
				->columns(array('id', 'title'));

		$posts = $model->fetchAll($select);

		if (count($posts) != 0) {
			return $posts;
		} else {
			return FALSE;
		}
	}

	public function getAllPostTitlesByType($post_type, $order) {
		$model = new self;

		$select = $model->select()
				->from($this->_name, 'id')
				->where('post_type_id=' . $post_type)
				->order('title ' . $order)
				->columns(array('id', 'title'));

		$posts = $model->fetchAll($select);

		if (count($posts) != 0) {
			return $posts;
		} else {
			return FALSE;
		}
	}

	public function getPublishedPostsPager($count, $page, $page_range, $order) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->setIntegrityCheck(false)
						->from(array('a' => $this->_name), 'id')
						->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
						->join(array('a_t' => 'post_type'), 'a_t.id = a.post_type_id', array('post_type_name' => 'a_t.name'))
						->columns('*')
						->where('publish = 1')
						->order('a.id ' . $order)
		);

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

	public function getAllPostsPager($count, $page, $page_range, $order) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->setIntegrityCheck(false)
						->from(array('a' => $this->_name), 'id')
						->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
						->join(array('a_t' => 'post_type'), 'a_t.id = a.post_type_id', array('post_type_name' => 'a_t.name'))
						->columns(array('a.id', 'a.post_type_id', 'a.user_id', 'a.title', 'a.annotation', 'a.text', 'a.image', 'a.views', 'a.date_create', 'a.date_edit'))
						->order('a.id ' . $order)
		);

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

	public function getAllPostsPagerByType($count, $page, $page_range, $post_type, $order) {
		$model = new self;

		$adapter = new Zend_Paginator_Adapter_DbTableSelect($model
						->select()
						->setIntegrityCheck(false)
						->from(array('a' => $this->_name), 'id')
						->join(array('u' => 'user'), 'u.id = a.user_id', array('user_login' => 'u.login'))
						->join(array('a_t' => 'post_type'), 'a_t.id = a.post_type_id', array('post_type_name' => 'a_t.name'))
						->columns('*')
						->where('post_type_id=' . $post_type)
						->where('publish = 1')
						->order('a.id ' . $order)
		);

		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($count);
		$paginator->setCurrentPageNumber($page);
		$paginator->setPageRange($page_range);

		return $paginator;
	}

	public function getLastPublishPost($count, $order) {
		$model = new self;

		$select = $model
				->select()
				->from($this->_name, 'id')
				->where('publish = 1')
				->columns(array('title', 'annotation', 'text', 'image', 'content_type_id'))
				->limit($count, 0)
				->order('id ' . $order);

		$result = $model->fetchAll($select);

		return $result;
	}

	public function getPublishPostTitlesByTypeName($post_type_name, $order) {
		$model = new self;

		$post_type = new Application_Model_DbTable_PostType();
		$post_type_data = $post_type->getItem(array('name' => $post_type_name));

		if (count($post_type_data) > 0) {
			$select = $model->select()
					->from($this->_name, 'id')
					->where('publish = 1 and post_type_id = ' . $post_type_data->id)
					->order('title ' . $order)
					->columns(array('id', 'title'));

			$posts = $model->fetchAll($select);

			if (count($posts) != 0) {
				return $posts;
			} else {
				return FALSE;
			}
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
				->join(array('pt' => 'post_type'), $this->db_href . '.post_type_id = pt.id', array('post_type_name' => 'pt.name'))
				->join(array('ct' => 'content_type'), $this->db_href . '.content_type_id = ct.id', array('content_type_name' => 'ct.name'))
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
				->join(array('u' => 'user'), $this->db_href . '.user_id = u.id', array('user_login' => 'u.login'))
				->join(array('pt' => 'post_type'), $this->db_href . '.post_type_id = pt.id', array('post_type_name' => 'pt.name'))
				->join(array('ct' => 'content_type'), $this->db_href . '.content_type_id = ct.id', array('content_type_name' => 'ct.name'));

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
