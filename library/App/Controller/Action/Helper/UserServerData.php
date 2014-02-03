<?php

class App_Controller_Action_Helper_UserServerData extends Zend_Controller_Action_Helper_Abstract {

	/**
	 * Метод GetUserIp возвращает ip пользователя
	 * 
	 * @param int $length
	 */
	public function preDispatch() {
		
	}

	public function GetUserIp() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	public function GetPreviousPage() {
		return (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "";
	}

}
