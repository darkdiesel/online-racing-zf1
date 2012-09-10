<?php
class Application_Model_Guestbook
{
	protected $_id;
	protected $_login;
	protected $_password;
	protected $_role_id
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
	
	public function __get($name)
    {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid user property');
        }
        return $this->$method();
    }
}