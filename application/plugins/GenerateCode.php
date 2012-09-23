<?php
class Generate extends Zend_Controller_Plugin_Abstract {
	/**
     * Метод GenerateCode Генерериет строку символов заданной длины
     * 
     * @param Zend_Controller_Request_Abstract $request
     * @param int $length
     */
    public function  GenerateCode(Zend_Controller_Request_Abstract $request, int $length) {
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
		$string = '';
			for ($i = 0; $i < $length; $i++) {
				$string .= substr($chars, rand(1, $numChars) - 1, 1);
			}
		return $string;
	}
}