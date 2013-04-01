<?php

class App_View_Helper_RenderContentTypeText extends Zend_View_Helper_Abstract {

    private $_render_text;
    private $_bbcode;

    public function RenderContentTypeText($text, $content_type) {

        switch ($content_type) {
            case 'text':
                $this->_render_text = $this->text($text);
                break;
            case 'bbcode':
                $this->_render_text = $this->bbcode($text);
                break;
            case 'full html':
                $this->_render_text = $this->fullhtml($text);
                break;
            default:

                break;
        }

        return $this->_render_text;
    }

    public function bbcode($text) {
        $bbcode = Zend_Markup::factory('Bbcode');
        //$bbcode->render($text);
        
        return $bbcode->render($text);
    }

    public function text($text) {
        
        
        return $this->view->escape($text);
    }

    public function fullhtml($text) {

        return $text;
    }

}