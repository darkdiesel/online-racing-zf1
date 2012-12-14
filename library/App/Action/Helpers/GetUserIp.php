<?php

class Zend_Controller_Action_Helper_GetUserIp extends Zend_Controller_Action_Helper_Abstract {

    /**
     * Метод get_real_ip возвращает ip пользователя
     * 
     * @param int $length
     */
    function get_real_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        } return $ip;
    }

}