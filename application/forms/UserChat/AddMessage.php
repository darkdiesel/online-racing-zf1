<?php

class Application_Form_UserChat_AddMessage extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/chat/addmessage');
        $this->setName('userChat');

        $this->setElementDecorators(array(
            'ViewHelper',
            array('HtmlTag', array('tag' => 'div', 'class' => 'controls_box'))
        ));

        $this->addElement('textarea', 'messageTextArea', array(
            'label' => $this->translate('Сообщение'),
            'placeholder' => $this->translate('Текст сообщения'),
            'cols' => 28,
            'rows' => 2,
            'maxlength' => 500,
            'required' => true,
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 500))
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'messageTextArea_Label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'messageTextArea_box')),
            )
        ));

        $this->addElement('hidden', 'last_load', array(
            'value' => 0
        ));
        $this->addElement('hidden', 'block_msg', array(
            'value' => 'no'
        ));

        $this->addElement('button', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Отправить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('button', 'reset', array(
            'ignore' => true,
            'class' => 'btn',
            'label' => $this->translate('Очистить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'reset form_actions_group'))
            )
        ));
    }

}

