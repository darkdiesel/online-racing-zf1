<?php

class Application_Model_User {

    protected $_id;
    protected $_login;
    protected $_password;
    protected $_last_login;
    protected $_activate;
    protected $_enabled;
    protected $_role_id;
    protected $_email;
    protected $_name;
    protected $_surname;
    protected $_country;
    protected $_city;
    protected $_birthday;
    protected $_gravatar;
    protected $_skype;
    protected $_icq;
    protected $_gtalk;
    protected $_www;
    protected $_vk;
    protected $_fb;
    protected $_tw;
    protected $_gp;
    protected $_created;
    protected $_about;

    public function __construct(array $options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
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

    public function setLogin($login) {
        $this->_login = (string) $login;
        return $this;
    }

    public function getLogin() {
        return $this->_login;
    }

    public function setPassword($password) {
        $this->_password = (string) $password;
        return $this;
    }

    public function getPassword() {
        return $this->_password;
    }
    
    public function setLast_login($last_login) {
        $this->_last_login = $last_login;
        return $this;
    }

    public function getLast_login() {
        return $this->_last_login;
    }

    public function setActivate($activate) {
        $this->_activate = (string) $activate;
        return $this;
    }

    public function getActivate() {
        return $this->_activate;
    }

    public function setEnabled($enabled) {
        $this->_enabled = (string) $enabled;
        return $this;
    }

    public function getEnabled() {
        return $this->_enabled;
    }

    public function setRole_id($role_id) {
        $this->_role_id = (int) $role_id;
        return $this;
    }

    public function getRole_id() {
        return $this->_role_id;
    }

    public function setEmail($email) {
        $this->_email = (string) $email;
        return $this;
    }

    public function getEmail() {
        return $this->_email;
    }
    
    public function setName($name) {
        $this->_name = (string) $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }
    
    public function setSurname($surname) {
        $this->_surname = (string) $surname;
        return $this;
    }

    public function getSurname() {
        return $this->_surname;
    }
    
    public function setCountry($coutry) {
        $this->_country = (string) $coutry;
        return $this;
    }

    public function getCountry() {
        return $this->_country;
    }
    
    public function setCity($city) {
        $this->_city = (string) $city;
        return $this;
    }

    public function getCity() {
        return $this->_city;
    }
    
    public function setBirthday($birthday) {
        $this->_birthday = $birthday;
        return $this;
    }

    public function getBirthday() {
        return $this->_birthday;
    }
    
    public function setGravatar($gravatar) {
        $this->_gravatar = $gravatar;
        return $this;
    }

    public function getGravatar() {
        return $this->_gravatar;
    }
    
    public function setSkype($skype) {
        $this->_skype = $skype;
        return $this;
    }

    public function getSkype() {
        return $this->_skype;
    }
    
    public function setIcq($icq) {
        $this->_icq = (int) $icq;
        return $this;
    }

    public function getIcq() {
        return $this->_icq;
    }
    
    public function setGtalk($gtalk) {
        $this->_gtalk = $gtalk;
        return $this;
    }

    public function getGtalk() {
        return $this->_gtalk;
    }
    
    public function setWww($www) {
        $this->_www = $www;
        return $this;
    }

    public function getWww() {
        return $this->_www;
    }
    
    public function setVk($vk) {
        $this->_vk = (string) $vk;
        return $this;
    }

    public function getVk() {
        return $this->_vk;
    }

    public function setFb($fb) {
        $this->_fb = (string) $fb;
        return $this;
    }

    public function getFb() {
        return $this->_fb;
    }

    public function setTw($tw) {
        $this->_tw = (string) $tw;
        return $this;
    }

    public function getTw() {
        return $this->_tw;
    }

    public function setGp($gp) {
        $this->_gp = (string) $gp;
        return $this;
    }

    public function getGp() {
        return $this->_gp;
    }

    public function setCreated($created) {
        $this->_created = $created;
        return $this;
    }

    public function getCreated() {
        return $this->_created;
    }
    
    public function setAbout($about) {
        $this->_about = $about;
        return $this;
    }

    public function getAbout() {
        return $this->_about;
    }

}