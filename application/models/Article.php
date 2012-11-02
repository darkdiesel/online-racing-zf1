<?php

class Application_Model_Article {

    protected $_id;
    protected $_user_id;
    protected $_type_id;
    protected $_title;
    protected $_text;
    protected $_date;
    protected $_date_edit;
    protected $_views;
    protected $_publish;
    protected $_last_ip;

    public function __construct(array $options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user_chat property');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user_chat property');
        }
        return $this->$method();
    }

    public function setOptions(array $options) {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    public function setId($id) {
        $this->_id = (int) $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }

    public function setUser_id($user_id) {
        $this->_user_id = (int) $user_id;
        return $this;
    }

    public function getUser_id() {
        return $this->_user_id;
    }
    
    public function setType_id($type_id) {
        $this->_type_id = (int) $type_id;
        return $this;
    }

    public function getType_id() {
        return $this->_type_id;
    }

    public function setTitle($title) {
        $this->_title = $title;
        return $this;
    }

    public function getTitle() {
        return $this->_title;
    }

    public function setText($text) {
        $this->_text = $text;
        return $this;
    }

    public function getText() {
        return $this->_text;
    }

    public function setDate($date) {
        $this->_date = $date;
        return $this;
    }

    public function getDate() {
        return $this->_date;
    }
    
    public function setDate_edit($date_edit) {
        $this->_date_edit = $date_edit;
        return $this;
    }

    public function getDate_edit() {
        return $this->_date_edit;
    }

    public function setViews($views) {
        $this->_views = (int) $views;
        return $this;
    }

    public function getViews() {
        return $this->_views;
    }
    
    public function setPublish($publish) {
        $this->_publish = (int) $publish;
        return $this;
    }

    public function getPublish() {
        return $this->_publish;
    }

    public function setLast_ip($last_ip) {
        $this->_last_ip = $last_ip;
        return $this;
    }

    public function getLast_ip() {
        return $this->_last_ip;
    }

}