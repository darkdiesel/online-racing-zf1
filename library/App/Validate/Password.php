 <?php

/**
 * App_Validate_Password
 * 
 * Валидация пароля
 * 
 * @author Александр Махомет aka San для http://zendframework.ru
 * 
 */
class App_Validate_Password extends Zend_Validate_Abstract 
{
    /**
     * Метка ошибки
     * @var const 
     */    
    const INVALID = 'passwordInvalid';

    /**
     * Метка ошибки
     * @var const 
     */    
    const INVALID_LENGTH = 'passwordBadLength';    
    
    /**
     * Текст ошибки
     * @var array 
     */
    protected $_messageTemplates = array(
        self::INVALID => 'Value does not appear to be a valid password',
        self::INVALID_LENGTH => 'Value should have more than 6 and less then 30 symbols'
    );

    /**
     * Проверка пароля
     * 
     * @param string $value значение которое поддается валидации
     */
    public function isValid($value) 
    {
        $this->_setValue($value);
        
        $validatorStringLength = new Zend_Validate_StringLength(7, 30);
        
        if (!$validatorStringLength->isValid($value)) {
            $this->_error(self::INVALID_LENGTH);
            return false;            
        }
        elseif (!preg_match("/^[~!@#\\$%\\^&\\*\\(\\)\\-_\\+=\\\\\/\\{\\}\\[\\].,\\?<>:;a-z0-9]*$/i", $value)) {
            $this->_error(self::INVALID);
            return false;            
        }

        return true;
    }
}