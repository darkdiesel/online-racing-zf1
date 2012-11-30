<?php

class Application_Model_Mod {

    protected $_id;
    protected $_game_id;
    protected $_name;
    protected $_developer;
    protected $_year;
    protected $_description;
    protected $_article_id;

    public function __construct(array $options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid mod property');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid mod property');
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

    public function setGameId($gameid) {
        $this->_game_id = (int) $gameid;
        return $this;
    }

    public function getGameId() {
        return $this->_game_id;
    }

    public function setName($name) {
        $this->_name = (string) $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }

    public function setDeveloper($developer) {
        $this->_developer = (string) $developer;
        return $this;
    }

    public function getDeveloper() {
        return $this->_developer;
    }

    public function setYear($year) {
        $this->_year = (string) $year;
        return $this;
    }

    public function getYear() {
        return $this->_year;
    }

    public function setDescription($description) {
        $this->_description = (string) $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setArticle_Id($id) {
        $this->_article_id = (int) $id;
        return $this;
    }

    public function getArticle_Id() {
        return $this->_article_id;
    }

}