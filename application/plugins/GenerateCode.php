<?php
class GenerateCode extends Zend_Controller_Plugin_Abstract {
	/**
     * Метод GenerateCode Генерериет строку символов заданной длины
     * 
     * @param int $length
     */
    static function  GenerateCodeString(int $length) {
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
		$string = '';
			for ($i = 0; $i < $length; $i++) {
				$string .= substr($chars, rand(1, $numChars) - 1, 1);
			}
		return $string;
	}
}