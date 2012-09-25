<?php

class Application_Form_UserRegisterForm extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        // loading validators
        Zend_Loader::loadClass('App_Validate_EqualInputs');
        Zend_Loader::loadClass('App_Validate_NoDbRecordExists');

        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction('/user/register');
        $this->setName('userRegister');
        $this->setAttrib('class', 'white_box');

        $this->addElement('text', 'login', array(
            'label' => $this->translate('Логин'),
            'placeholder' => $this->translate('Логин'),
            'required' => true,
            'class' => 'x_field',
            'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                array('regex', false, '/^[a-z0-9_-]{3,20}$/'),
                array('StringLength', true, array('min' => 3, 'max' => 20)),
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
            'required' => true,
            'class' => 'x_field',
            'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                'EmailAddress',
                new App_Validate_NoDbRecordExists('user', 'email'),
                array('StringLength', true, array('min' => 5, 'max' => 50))
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
            'required' => true,
            'class' => 'x_field',
            'filters' => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('StringLength', true, array('min' => 6, 'max' => 25))
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
            'AllowEmpty' => false,
            'class' => 'x_field',
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
        ));

        $this->addElement('reset', 'reset', array(
            'label' => "",
            'ignore' => true,
            'class' => 'btn',
            'label' => $this->translate('Сбросить'),
        ));
    }

}