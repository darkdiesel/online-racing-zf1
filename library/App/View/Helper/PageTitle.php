<?php

class App_View_Helper_PageTitle extends Zend_View_Helper_Abstract {

    private $_page_title;

    public function pageTitle($page_title = null) {
        $page_title = (string) $page_title;
        if ($page_title !== '') {
            $this->_page_title = (string) $page_title;
        }
        return $this;
    }

    public function render() {
        if ($this->_page_title) {
            return $this->_page_title;
        } else {
            return '';
        }
    }

    public function __toString() {
        return $this->render();
    }

}