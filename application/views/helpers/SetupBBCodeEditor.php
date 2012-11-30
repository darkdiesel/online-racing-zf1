<?php

class Zend_View_Helper_SetupBBCodeEditor extends Zend_View_Helper_Abstract {

    function setupBBCodeEditor($textareaId) {
        return "<script type=\"text/javascript\">
                CKEDITOR.replace('" . $textareaId . "', {
                    extraPlugins : 'bbcode',
                    toolbar: [
                        [ 'Source', '-', 'Save', 'NewPage', '-', 'Undo', 'Redo' ],
                        [ 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat' ],
                        [ 'Link', 'Unlink', 'Image' ],
                        '/',
                        [ 'FontSize', 'Bold', 'Italic', 'Underline' ],
                        [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ],
                        [ 'TextColor', '-', 'Smiley', 'SpecialChar', '-', 'Maximize' ]
                    ]
                    });
                </script>";
    }    
}