<?php

class Application_Form_Auth_Login extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post')
            ->setName('default-auth-login');

        $this->setAttribs(
            array(
                'class' => 'block-item block-item-form block-item-form-w-320 center-block',
                'id' => 'default-auth-login',
            )
        );

        // decorators for this form
        $this->addDecorators(array('formElements', 'form'));

        // Add an email element
        $this->addElement(
            'text', 'loginemail', array(
                'label' => 'E-mail:',
                'placeholder' => 'E-mail',
                'data-title' => $this->translate(
                        'Введите свой электронный почтовый ящик. Пример: example@mail.com.'
                    ),
                'required' => true,
                'class' => 'form-control tooltip-field',
                'data-placement' => 'bottom',

                'maxlength' => 255,
                'size' => 255,
                'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
                'validators' => array(
                    'EmailAddress',
                    array('StringLength', true, array('min' => 5, 'max' => 255)),
                    new App_Validate_DbRecordExists('user', 'email')
                ),
                'decorators' => array(
                    'ViewHelper', 'HtmlTag', 'label', 'Errors',
                    array('Label', array('class' => 'control-label')),
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('class' => '')),
                )
            )
        );

        $this->addElement(
            'password', 'loginpassword', array(
                'label' => $this->translate('Пароль'),
                'placeholder' => $this->translate('Пароль'),
                'data-title' => $this->translate(
                        'Введите пароль от своей учетной записи.'
                    ),
                'required' => true,
                'class' => 'form-control tooltip-field',
                'data-placement' => 'bottom',
                'filters' => array('StripTags', 'StringTrim'),
                'maxlength' => 25,
                'validators' => array(
                    array('StringLength', true, array('min' => 6))
                ),
                'decorators' => array(
                    'ViewHelper', 'HtmlTag', 'label', 'Errors',
                    array('Label', array('class' => 'control-label')),
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('class' => '')),
                )
            )
        );

        $this->addElement(
            'checkbox', 'remember', array(
                'label' => $this->translate('Запомнить меня'),
                'data-title' => $this->translate(
                        'Отметьте поле, чтобы не авторизовываться при следующем посещении сайта.'
                    ),
                'data-placeholder' => 'left',
                'class' => 'tooltip-field',
                'data-placement' => 'bottom',
                'decorators' => array(array('ViewScript', array(
                    'viewScript' => 'viewScript/form_checkbox_bootstrap3.phtml'
                )))
            )
        );

        $csrfToken = new Zend_Form_Element_Hash('default_auth_login_csrf_token');
        $csrfToken->setSalt(md5(microtime() . uniqid()))
            ->setDecorators(
                array(
                    'ViewHelper', 'HtmlTag', 'label', 'Errors',
                    array('Label', array('class' => 'control-label')),
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('class' => ''))));

        $this->addElement(
            'submit', 'submit', array(
                'ignore' => true,
                'class' => 'btn btn-primary',
                'label' => $this->translate('Войти'),
                'decorators' => array(
                    'ViewHelper', 'HtmlTag',
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
                )
            )
        );

        $this->addElement(
            'button', 'restore_pass', array(
                'ignore' => true,
                'class' => 'btn btn-danger',
                'onClick' => "location.href='/user/restore-pass'",
                'label' => $this->translate('Забыли пароль?'),
                'decorators' => array(
                    'ViewHelper', 'HtmlTag',
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
                )
            )
        );

        // add elements to form
        $this->addElement($csrfToken);

        $this->addDisplayGroup(
            array(
                $this->getElement('submit'),
                $this->getElement('restore_pass')
            ), 'form_actions', array()
        );

        $this->getDisplayGroup('form_actions')->setDecorators(
            array(
                'FormElements',
                //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
                //'Fieldset',
                array(array('outerHtmlTag' => 'HtmlTag'),
                    array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
            )
        );
    }

}
