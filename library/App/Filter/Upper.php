<?php

class App_Filter_Upper implements Zend_Filter_Interface {

    public function filter($value) {
        $valueFiltered = strtoupper(substr($value, 0, 1)) . strtolower(substr($value, 1, strlen($value) - 1));

        return $valueFiltered;
    }

}