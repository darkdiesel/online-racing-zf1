<?php


class Peshkov_View_Helper_GetDecorator extends Zend_View_Helper_Abstract
{

    protected $_decoratorName;
    protected $_decorator;

    public function getDecorator($name = 'TwitterBootstrap3')
    {
        $this->_decoratorName = $name;

        return $this;
    }

    public function formDecorators()
    {

        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    array('FormErrors'),
                    array('FormElements'),
                    array('Form')
                );
                break;
        }

        return $this->_decorator;
    }

    public function elementDecorators()
    {

        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    array('ViewHelper'),
                    //array('HtmlTag', array('tag' => 'div', 'class' => '')),
                    array('Label', array('class' => 'control-label')),
                    array('Errors'),
                    array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
                );
                break;
        }

        return $this->_decorator;
    }

    public function fileDecorators()
    {

        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    array('File'),
                    //array('HtmlTag', array('tag' => 'div', 'class' => '')),
                    array('Label', array('class' => 'control-label')),
                    array('Errors'),
                    array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
                );
                break;
        }

        return $this->_decorator;
    }

    public function buttonDecorators()
    {

        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    array('ViewHelper'),
                    array('HtmlTag', array('tag' => 'div', 'class' => 'form-group')),
                );
                break;
        }

        return $this->_decorator;
    }

    public function checkboxDecorators()
    {
        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    'ViewHelper', 'HtmlTag', 'Errors',
                    array('label', array('class' => 'control-label', 'placement' => 'APPEND')),
                    array('HtmlTag', array('tag' => 'span')),
                    array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
                );
                break;
        }

        return $this->_decorator;
    }

    public function displayGroupDecorators()
    {

        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    array('FormElements'),
                    //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
                    array('Fieldset'),
                    //array(array('outerHtmlTag' => 'HtmlTag'),
//                array('tag' => 'div', 'class' => 'form-actions clearfix')),
                );
                break;
        }

        return $this->_decorator;
    }

    public function formActionsGroupDecorators()
    {

        switch ($this->_decoratorName) {
            case 'TwitterBootstrap3':
                $this->_decorator = array(
                    array('FormElements'),
                    //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
                    array(array('outerHtmlTag' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-actions clearfix')),
                    array('Fieldset'),

                );
                break;
        }

        return $this->_decorator;
    }
}