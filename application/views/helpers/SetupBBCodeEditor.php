<?php

class Zend_View_Helper_SetupBBCodeEditor extends Zend_View_Helper_Abstract {

    function setupBBCodeEditor($textareaId) {
        return "
                CKEDITOR.replace('{$textareaId}', {
                    extraPlugins : 'bbcode,font,smiley,find,selectall,colorbutton,wordcount,limitcharcount',
		removePlugins: 'bidi,dialogadvtab,div,filebrowser,flash,forms,horizontalrule,iframe,justify,liststyle,pagebreak,showborders,stylescombo,table,tabletools,templates',
        // Width and height are not supported in the BBCode format, so object resizing is disabled.
        disableObjectResizing: true,
        // Define font sizes in percent values.
        fontSize_sizes: \"30/30%;50/50%;100/100%;120/120%;150/150%;200/200%;300/300%\",
        toolbar: [
          [ 'Source' ],
          [ 'Save', 'NewPage', '-', 'Undo', 'Redo' ],
          [ 'Find', 'Replace', '-', 'SelectAll' ],
          [ 'Link', 'Unlink', 'Image', 'Smiley', 'SpecialChar' ],
          '/',
          [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ],
          [ 'FontSize' ],
          [ 'TextColor' ],
          [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ],
          [ 'Maximize' ],
          [ 'About' , 'LimitCharCount' ]
        ],
                    });
                ";
    }

}