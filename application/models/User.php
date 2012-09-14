<?php
class Application_Model_User
{
	protected $_id;
	protected $_login;
	protected $_password;
    protected $_activate;
    protected $_enabled;
	protected $_role_id;
	protected $_email;
	protected $_name;
	protected $_surname;
	protected $_country;
	protected $_city;
	protected $_birthday;
	protected $_skype;
	protected $_icq;
	protected $_www;
	protected $_vk;
	protected $_fb;
	protected $_tw;
	protected $_gp;
	protected $_about;
	
	public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }
	
	public function __set($name, $value)
    {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
        }
        $this->$method($value);
    }

	public function __get($name)
    {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
        }
        return $this->$method();
    }

    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    public function setId($id)
    {
        $this->_id = (int) $id;
        return $this;
    }
 
    public function getId()
    {
        return $this->_id;
    }

    public function setLogin($login)
    {
        $this->_login = (string) $login;
        return $this;
    }
 
    public function getLogin()
    {
        return $this->_login;
    }

    public function setPassword($password)
    {
        $this->_password = (string) $password;
        return $this;
    }
 
    public function getPassword()
    {
        return $this->_password;
    }

    public function setActivate($activate)
    {
        $this->_activate = (string) $activate;
        return $this;
    }
 
    public function getActivate()
    {
        return $this->_activate;
    }

    public function setEnabled($enabled)
    {
        $this->_enabled = (string) $enabled;
        return $this;
    }
 
    public function getEnabled()
    {
        return $this->_enabled;
    }

    public function setRole_id($role_id)
    {
        $this->_role_id = (int) $role_id;
        return $this;
    }
 
    public function getRole_id()
    {
        return $this->_role_id;
    }

    public function setEmail($email)
    {
        $this->_email = (string) $email;
        return $this;
    }
 
    public function getEmail()
    {
        return $this->_email;
    }

    public function setVk($vk)
    {
        $this->_vk = (string) $vk;
        return $this;
    }
 
    public function getVk()
    {
        return $this->_vk;
    }

    public function setFb($fb)
    {
        $this->_fb = (string) $fb;
        return $this;
    }
 
    public function getFb()
    {
        return $this->_fb;
    }

    public function setTw($tw)
    {
        $this->_tw = (string) $tw;
        return $this;
    }
 
    public function getTw()
    {
        return $this->_tw;
    }

    public function setGp($gp)
    {
        $this->_gp = (string) $gp;
        return $this;
    }
 
    public function getGp()
    {
        return $this->_gp;
    }

    public function setAbout($about)
    {
        $this->_about = (string) $about;
        return $this;
    }
 
    public function getAbout()
    {
        return $this->_about;
    }
}