<?php

class Application_Form_User_RestorePasswd extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction('/user/restore-passwd');
        $this->setName('userRestorePasswd');
        $this->setAttrib('class', 'white_box white_box_size_m');

        // Add an email element
        $this->addElement('text', 'email', array(
            'label' => 'E-mail',
            'placeholder' => 'E-mail',
            'title' => $this->translate('Введи e-mail адрес, на который зарегестрирован ваш пользователь.'),
            'required' => true,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                'EmailAddress',
                new App_Validate_DbRecordExists('user', 'email')
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
        $this->addElement('text', 'confirmemail', array(
            'label' => $this->translate('Подтвердите E-mail'),
            'placeholder' => $this->translate('Подтвердите E-mail'),
            'title' => $this->translate('Повторите e-mail.'),
            'AllowEmpty' => false,
            'class' => 'x_field tooltip_field',
            'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
            'validators' => array(
                new App_Validate_EqualInputs('email')
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement(
                new Zend_Form_Element_Captcha('captcha', array(
                    'ignore' => true, // игнорируем, чтобы не получать значение элемента при вызове
                    // метода getValues() нашей формы
                    'label' => 'captchaLabel',
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
            'label' => $this->translate('Восстановить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('reset', 'reset', array(
            'ignore' => true,
            'class' => 'btn btn-default',
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