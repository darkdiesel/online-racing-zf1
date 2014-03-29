<?php

class Bootstrap_View_Helper_Modal extends Zend_View_Helper_Abstract
{
    private $_settings;
    private $_html;

    private function _initSettings($settings)
    {
        $this->_settings = array(
            'showHeader' => true,
            'title' => NULL,
            'class' => NULL,
            'id' => NULL,
            'showFooter' => false,
            'closeFooterBtnText' => $this->view->translate('Закрыть'),
            'saveFooterBtnText' => $this->view->translate('Сохранить изминения'),
            'sizeClass' => 'modal-lg',
            'content' => NULL,
        );

        foreach ($settings as $name => $value) {
            $this->_settings[$name] = $value;
        }

        $this->_html = '';
    }


    /*
     * TODO: Make aria-labelledby property settable.
     */
    public function Modal($settings)
    {
        $this->_initSettings($settings);

        $this->_html .= '<div class="modal fade ' . $this->_settings['class'] . '" id="' . $this->_settings['id'] . '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">';
        $this->_html .= '<div class="modal-dialog ' . $this->_settings['sizeClass'] . '">';
        $this->_html .= '<div class="modal-content">';

        if ($this->_settings['showHeader']) :
            $this->_html .= '<div class="modal-header">';
            $this->_html .= '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
            $this->_html .= '<h4 class="modal-title" id="myModalLabel">';
            $this->_html .= $this->_settings['title'];
            $this->_html .= '</h4>';
            $this->_html .= '</div>';
        endif;

        $this->_html .= '<div class="modal-body">';
        $this->_html .= $this->_settings['content'];
        $this->_html .= '</div>';

        if ($this->_settings['showFooter']) :
            $this->_html .= '<div class="modal-footer">';
            $this->_html .= '<button type="button" class="btn btn-default" data-dismiss="modal">' . $this->_settings['closeFooterBtnText'] . '</button>';
            $this->_html .= '<button type="button" class="btn btn-primary">' . $this->_settings['saveFooterBtnText'] . '</button>';
            $this->_html .= '</div>';
        endif;

        $this->_html .= '</div>';
        $this->_html .= '</div>';
        $this->_html .= '</div>';

        return $this;
    }

    public function __toString()
    {
        return $this->_html;
    }
}