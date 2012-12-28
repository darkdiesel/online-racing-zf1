<?php

class Application_Form_User_Register extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction('/user/register');
        $this->setName('userRegister');
        $this->setAttrib('class', 'white_box');

        $this->addElement('text', 'login', array(
            'label' => $this->translate('Логин'),
            'placeholder' => $this->translate('Логин'),
            'title' => $this->translate('Длина поля должна быть от 5 до 20 символов, содержать только латинские буквы, цифпы и символы -_.'),
            'required' => true,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                array('regex', false, '/^[a-zA-Z0-9_-]{5,20}$/'),
                array('StringLength', true, array('min' => 5, 'max' => 20)),
                new App_Validate_NoDbRecordExists('user', 'login')
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'control-label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group')),
                array('HtmlTag', array('class' => 'controls')),
            )
        ));

        $this->addElement('text', 'email', array(
            'label' => 'E-mail',
            'placeholder' => 'E-mail',
            'title' => $this->translate('Поле должно содержать правильный адрес вашего электронного почтового ящика. Пример: example@mail.com.'),
            'required' => true,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                'EmailAddress',
                new App_Validate_NoDbRecordExists('user', 'email'),
                array('StringLength', true, array('min' => 5))
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'control-label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group')),
                array('HtmlTag', array('class' => 'controls')),
            )
        ));

        $this->addElement('password', 'password', array(
            'label' => $this->translate('Пароль'),
            'placeholder' => $this->translate('Пароль'),
            'title' => $this->translate('Длина поля должна быть от 6 до 25 символов, содержать только латиские буквы, цифры и символы -_.'),
            'required' => true,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('StringLength', true, array('min' => 6))
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'control-label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group')),
                array('HtmlTag', array('class' => 'controls')),
            )
        ));

        $this->addElement('password', 'confirmpassword', array(
            'label' => $this->translate('Подтвердите пароль'),
            'placeholder' => $this->translate('Подтвердите пароль'),
            'title' => $this->translate('Значение поля должно совпадать со значеним предыдущего поля.'),
            'AllowEmpty' => false,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                new App_Validate_EqualInputs('password')
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'control-label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group')),
                array('HtmlTag', array('class' => 'controls')),
            )
        ));

        $this->addElement(
                new Zend_Form_Element_Captcha('captcha', array(
                    'ignore' => true, // игнорируем, чтобы не получать значение элемента при вызове
                    // метода getValues() нашей формы
                    //'label' => 'captchaLabel',
                    'captcha' => array(
                        'captcha' => 'ReCaptcha',
                        'pubKey' => '6LdvedYSAAAAALfZ46Sx1yYF75erQzJdkZ0OG2Kt', // для получения ключей, нужно зарегистроваться
                        'privKey' => '6LdvedYSAAAAALTNnQNU_J4z_LYEE8A01CfFZa_D', // в сервисе ReCaptcha
                    ),
                    'captchaOptions' => array('theme' => 'white', // возможны варианты 'red' | 'white'
                        // | 'blackglass' | 'clean' | 'custom'
                        'lang' => 'ru'), // здесь также возможны 'en', 'nl',
                    // 'fr', 'de', 'pt', 'ru', 'es', 'tr'
                    // Captcha использует свой собственный декоратор, поэтому, для корректного ее отображения
                    // декоратор должен быть задан примерно следующим образом:
                    'decorators' => array(
                        array('Captcha'),
                        array('Errors'),
                    )
                )));

        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Зарегестрировать'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('reset', 'reset', array(
            'label' => "",
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