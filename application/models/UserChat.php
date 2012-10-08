<?php

class Application_Model_UserChat
{
    protected $_id;
    protected $_user_id;
    protected $_message;
    protected $_date;


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
        return $this->_id;
    }
    
    public function setMessage($message) {
        $this->_message = (int) $message;
        return $this;
    }

    public function getMessage() {
        return $this->_message;
    }
    
    public function setDate($date) {
        $this->_date = (int) $date;
        return $this;
    }

    public function getDate() {
        return $this->_date;
    }

}

