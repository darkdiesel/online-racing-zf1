<?php

class Application_Form_UserLoginForm extends Zend_Form {

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
            )
        ));

        $this->addElement('password', 'loginpassword', array(
            'label' => 'Пароль:',
            'placeholder' => 'Пароль',
            'required' => true,
            'class' => 'x_field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('StringLength', true, array('min' => 6, 'max' => 25))
            )
        ));

        $this->addElement('checkbox', 'remember', array(
            'label' => 'Запомнить меня',
        ));

        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => 'Войти',
        ));

        $this->addElement('reset', 'reset', array(
            'ignore' => true,
            'class' => 'btn',
            'label' => 'Сбросить',
        ));
    }

}