<?php

class App_Controller_Action_Helper_GenerateCode extends Zend_Controller_Action_Helper_Abstract {
    /**
     * Метод GenerateCodeString Генерериет строку символов заданной длины
     * 
     * @param int $length
     */
    static function  GenerateCodeString($length) {
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
		$string = '';
			for ($i = 0; $i < $length; $i++) {
				$string .= substr($chars, rand(1, $numChars) - 1, 1);
			}
		return $string;
	}
}