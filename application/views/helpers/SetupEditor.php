<?php

class Zend_View_Helper_SetupEditor extends Zend_View_Helper_Abstract {

    function setupEditor($textareaId) {
        return "<script type=\"text/javascript\">
                CKEDITOR.replace('" . $textareaId . "', 
                    {extraPlugins : 'bbcode'});
                </script>";
    }

}