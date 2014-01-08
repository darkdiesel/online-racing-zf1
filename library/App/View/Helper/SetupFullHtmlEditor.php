<?php

class App_View_Helper_SetupFullHtmlEditor extends Zend_View_Helper_Abstract {

    function SetupFullHtmlEditor($textareaId) {
        return "CKEDITOR.replace('" . $textareaId . "', {
            extraPlugins : 'iframe,iframedialog,font,smiley,find,selectall,colorbutton,wordcount,oembed',
            disableObjectResizing: true,
            fontSize_sizes: \"30/30%;50/50%;100/100%;120/120%;150/150%;200/200%;300/300%\",
            toolbar: [
              [ 'Source' ],
              [ 'Save', 'NewPage', '-', 'Undo', 'Redo' ],
              [ 'Find', 'Replace', '-', 'SelectAll' ],
              [ 'Link', 'Unlink', 'Image', 'Smiley', 'SpecialChar', 'oembed' ],
              '/',
              [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ],
              [ 'Font', 'FontSize' ],
              [ 'TextColor' ],
              [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ],
              [ 'Maximize' ],
              [ 'About' ]
            ]
        });";
    }

}