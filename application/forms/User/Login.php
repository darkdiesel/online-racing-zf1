<?php

class Application_Form_User_Login extends Zend_Form {

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
        $this->setAttrib('class', 'white_box white_box_size_s');

        // Add an email element
        $this->addElement('text', 'loginemail', array(
            'label' => 'E-mail:',
            'placeholder' => 'E-mail',
            'title' => $this->translate('Введите свой электронный почтовый ящик. Пример: example@mail.com.'),
            'required' => true,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StripTags', 'StringTrim', 'StringToLower'),
            'maxlength' => 255,
            'validators' => array(
                'EmailAddress',
                array('StringLength', true, array('min' => 5, 'max' => 255)),
                new App_Validate_DbRecordExists('user', 'email')
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
            'title' => $this->translate('Введите пароль от своей учетной записи.'),
            'required' => true,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim'),
            'maxlength' => 25,
            'validators' => array(
                array('StringLength', true, array('min' => 6))
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
            'title' => $this->translate('Отметьте поле, чтобы не авторизовываться при следующем посещении сайта.'),
            'data-placeholder' => 'left',
            'class' => 'tooltip_field',
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box checkbox')),
                array('HtmlTag', array('tag' => 'span', 'class' => 'element_tag')),
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

        $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'form_actions', array());

        $this->getDisplayGroup('form_actions')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form_actions display_group')),
        ));
    }

}