<?php

class App_View_Helper_CKFinder extends Zend_View_Helper_Abstract {

	public function cKFinder() {
		return $this;
	}

	public function init() {
		$this->view->headScript()->appendFile($this->view->baseUrl("library/ckfinder/ckfinder.js"));
		//return $this;
	}
	
	public function setupCKEditor($editor){
		return "var ck_editor_finder = CKEDITOR.instances['{$editor}'];"
		. "if (ck_editor_finder) {"
		. "CKFinder.setupCKEditor(ck_editor_finder, '/library/ckfinder/' );"
		."}";
	}

}
