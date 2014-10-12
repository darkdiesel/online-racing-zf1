<?php

class Peshkov_View_Helper_T extends Zend_View_Helper_Abstract
{
    /**
     *  t() translate text
     *
     * @param string $text
     * @return string
     */
    public function t($text = null)
    {
        return $this->view->translate($text);
    }



}
