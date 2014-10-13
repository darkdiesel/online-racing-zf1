<?php

class Peshkov_View_Helper_PageIcon extends Zend_View_Helper_Abstract {

    private $_pageIcon;

    public function pageIcon($pageIcon = null) {
        $pageIcon = (string) $pageIcon;
        if ($pageIcon !== '') {
            $this->_pageIcon = (string) $pageIcon;
        }
        return $this;
    }

    public function render() {
        if ($this->_pageIcon) {
            return $this->_pageIcon;
        } else {
            return '';
        }
    }

    public function __toString() {
        return $this->render();
    }

}