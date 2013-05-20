<?php

class App_View_Helper_CKEditor extends Zend_View_Helper_Abstract {
    
    function ckeditor() {
        //$this->view->headScript()->appendFile($this->view->baseUrl("js/ckeditor/ckeditor.js"));
        $this->view->headScript()->appendFile($this->view->baseUrl("libraries/ckeditor/ckeditor.js"));
    }
}