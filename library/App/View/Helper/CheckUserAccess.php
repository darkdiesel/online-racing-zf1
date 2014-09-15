<?php

class App_View_Helper_CheckUserAccess extends Zend_View_Helper_Abstract
{

    function checkUserAccess($resource, $privilege)
    {
        $acl = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Acl');
        return $acl->checkUserAccess($resource, $privilege);
    }

}
