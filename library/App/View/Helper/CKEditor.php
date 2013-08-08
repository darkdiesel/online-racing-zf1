<?php

class App_View_Helper_CKEditor extends Zend_View_Helper_Abstract {
    
    public function cKEditor() {
        return $this;
    }
    
    public function setup() {
        $this->view->headScript()->appendFile($this->view->baseUrl("libraries/ckeditor/ckeditor.js"));
        //return $this;
    }
    
    public function replace($editor_mode, $element_id) {
        switch ($editor_mode){
            case "bbcode":
                return $this->bbcode($element_id);
                break;
            case "html":
                return $this->html($element_id);
                break;
            default:
                
                break;
        }
    }
    
    private function bbcode($id){
        $configs = "";
        
        return $result = "CKEDITOR.replace({$id} ,{{$configs}})";
        return $result;
    }
    
    private function html($id){
        $configs = "";
        
        $result = "CKEDITOR.replace({$id} ,{{$configs}})";
        return $result;
    }
}