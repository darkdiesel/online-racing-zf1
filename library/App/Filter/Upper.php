<?php

class App_Filter_Upper implements Zend_Filter_Interface {

    public function filter($value) {
        if($value){
            $valueFiltered = mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8');
            $valueFiltered .= mb_strtolower(mb_substr($value, 1, mb_strlen($value, 'UTF-8') - 1, 'UTF-8'), 'UTF-8');

            return $valueFiltered;
        } else {
            return $value;
        }
    }

}