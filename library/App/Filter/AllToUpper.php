<?php

class App_Filter_AllToUpper implements Zend_Filter_Interface {

    public function filter($value) {
        $valueFiltered = strtoupper($value);

        return $valueFiltered;
    }

}