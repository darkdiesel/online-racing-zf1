<?php

class Application_Model_League {

    protected $_id;
    protected $_name;
    protected $_logo;
    protected $_description;
    protected $_date_create;
    protected $_date_edit;

    public function __construct(array $options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid role property');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid role property');
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

    public function setName($name) {
        $this->_name = (string) $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }
    
    public function setLogo($logo) {
        $this->_logo = (string) $logo;
        return $this;
    }

    public function getLogo() {
        return $this->_logo;
    }
    
    public function setDescription($description) {
        $this->_description = (string) $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setDate_create($date_create) {
        $this->_date_create = $date_create;
        return $this;
    }

    public function getDate_create() {
        return $this->_date_create;
    }

    public function setDate_edit($date_edit) {
        $this->_date_edit = $date_edit;
        return $this;
    }

    public function getDate_edit() {
        return $this->_date_edit;
    }

}