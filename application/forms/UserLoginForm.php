<?php

class Application_Form_UserLoginForm extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction('/user/login');
        $this->setName('userlogin');
        $this->setAttrib('class', 'white_box');
        $isEmptyMessage = 'Значение является обязательным и не может быть пустым';

        // Add an email element
        $this->addElement('text', 'loginemail', array(
            'label' => 'E-mail:',
            'placeholder' => 'E-mail',
            'required' => true,
            'class' => 'x_field',
            'filters' => array('StripTags', 'StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                'EmailAddress',
                array('StringLength', true, array('min' => 5, 'max' => 100))
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('password', 'loginpassword', array(
            'label' => $this->translate('Пароль'),
            'placeholder' => $this->translate('Пароль'),
            'required' => true,
            'class' => 'x_field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('StringLength', true, array('min' => 6, 'max' => 25))
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('checkbox', 'remember', array(
            'label' => $this->translate('Запомнить меня'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box checkbox')),
                array('HtmlTag', array('tag' => 'span','class' => 'element_tag')),
            )
        ));

        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Войти'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('reset', 'reset', array(
            'ignore' => true,
            'class' => 'btn',
            'label' => $this->translate('Сбросить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'reset form_actions_group'))
            )
        ));
    }

}