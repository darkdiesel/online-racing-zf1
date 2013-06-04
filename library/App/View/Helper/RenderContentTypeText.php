<?php

class App_View_Helper_RenderContentTypeText extends Zend_View_Helper_Abstract {

    private $_size_text;
    private $_render_text;
    private $_bbcode;

    public function RenderContentTypeText($text, $content_type, $text_size = 0) {
        $this->_render_text = $text;

        if ($text_size) {
            $this->_render_text = $this->view->truncate($this->_render_text)->toLength($text_size)->render();
        }

        switch ($content_type) {
            case 'text':
                $this->_render_text = $this->text($this->_render_text);
                break;
            case 'bbcode':
                $this->_render_text = $this->bbcode($this->_render_text);
                break;
            case 'full html':
                $this->_render_text = $this->fullhtml($this->_render_text);
                break;
            default:

                break;
        }
        return $this->_render_text;
    }

    public function bbcode($text) {
        $bbcode = Zend_Markup::factory('Bbcode');
        return $bbcode->render($text);
    }

    public function text($text) {
        return $this->view->escape($text);
    }

    public function fullhtml($text) {
        return $text;
    }

}