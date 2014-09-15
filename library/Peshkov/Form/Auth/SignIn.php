<?php

class Peshkov_Form_Auth_SignIn extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        // Set  redirect url if user authorizing successful.
        $redirectToUrl = $request->getParam('redirectTo');

        if (empty($redirectToUrl)) {
            $redirectToUrl = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "";

            $config = Zend_Registry::get('config');

            if (parse_url($redirectToUrl, PHP_URL_HOST) == parse_url($config->resources->frontController->baseUrl, PHP_URL_HOST)) {
                $this->setAction($this->getView()->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-in'), 'default', true) . "?redirectTo=" . $redirectToUrl);
            } else {
                $this->setAction($this->getView()->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-in'), 'default', true));
            }
        } else {
            $this->setAction($this->getView()->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-in'), 'default', true) . "?redirectTo=" . $redirectToUrl);
        }

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'auth-sign-in'
            )
        )
            ->setName('authSignIn')
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $email = new Zend_Form_Element_Text('Email');
        $email->setLabel($this->translate('E-mail'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('E-mail'))
            ->setAttribs(array(
                'data-title' => $this->translate('Введите свой электронный почтовый ящик. Пример: example@mail.com.'),
                'data-placement' => 'bottom'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('EmailAddress')
            ->addValidator('stringLength', false, array(5, 255, 'UTF-8'))
            ->addValidator(
                'Db_RecordExists', false,
                array(
                    'table' => 'user',
                    'field' => 'Email',
                )
            )
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->addFilter('StringToLower')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $password = new Zend_Form_Element_Password('Password');
        $password->setLabel($this->translate('Пароль'))
            ->setOptions(array('maxLength' => 40, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Пароль'))
            ->setAttribs(array(
                'data-title' => $this->translate('Введите пароль от своей учетной записи.'),
                'data-placement' => 'bottom'))
            ->setRequired(true)
            ->addValidator('stringLength', false, array(6, 40, 'UTF-8'))
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $rememberMe = new Zend_Form_Element_Checkbox('RememberMe');
        $rememberMe->setLabel($this->translate('Запомнить меня?'))
            ->setValue(1)
            ->setDecorators(
                array(
                    'ViewHelper', 'HtmlTag', 'Errors',
                    array('label', array('class' => 'control-label', 'placement' => 'APPEND')),
                    array('HtmlTag', array('tag' => 'span')),
                    array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
                )
            );

        $csrfToken = new Zend_Form_Element_Hash('defaultAuthSignInCsrfToken');
        $csrfToken->setSalt(md5(microtime() . uniqid()))
            ->setDecorators(
                array(
                    'ViewHelper', 'HtmlTag', 'label', 'Errors',
                    array('Label', array('class' => 'control-label')),
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('class' => ''))));

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Войти'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $defaultUserRestorePasswordUrl = $this->getView()->url(
            array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default'
        );

        $forgetPassword = new Zend_Form_Element_Button('ForgetPassword');
        $forgetPassword->setLabel($this->translate('Забыли пароль?'))
            ->setAttrib('onClick', "location.href='{$defaultUserRestorePasswordUrl}'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($email)
            ->addElement($password)
            ->addElement($rememberMe);

        $this->addElement($csrfToken);

        $this->addElement($submit)
            ->addElement($forgetPassword);

        $this->addDisplayGroup(
            array(
                $this->getElement('Submit'),
                $this->getElement('ForgetPassword'),
            ), 'FormActions'
        );

        $this->getDisplayGroup('FormActions')
            ->setOrder(100)
            ->setDecorators($this->getView()->getDecorator()->formActionsGroupDecorators());
    }

}
