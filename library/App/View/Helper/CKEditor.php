<?php

class App_View_Helper_CKEditor extends Zend_View_Helper_Abstract {

	public function cKEditor() {
		return $this;
	}
	
	public function init(){
		$this->view->headScript()->appendFile($this->view->baseUrl("library/ckeditor/ckeditor.js"));
		//return $this;
	}

	public function replace($editor_mode, $element_id) {
		switch ($editor_mode) {
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
	
	public function setupBBCodeEditor($textareaId) {
        return "
                CKEDITOR.replace('{$textareaId}', {
                    extraPlugins : 'bbcode,font,smiley,find,selectall,colorbutton,wordcount',
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
          [ 'About' ]
        ],
                    });
                ";
    }
	
	public function SetupFullHtmlEditor($textareaId) {
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

	private function bbcode($id) {
		$configs = "";

		return $result = "CKEDITOR.replace({$id} ,{{$configs}})";
		return $result;
	}

	private function html($id) {
		$configs = "";

		$result = "CKEDITOR.replace({$id} ,{{$configs}})";
		return $result;
	}

}
