<?php

class Peshkov_Form_User_Activate extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $defaultUserActivateUrl = $this->getView()->url(array('module' => 'default', 'controller' => 'user', 'action' => 'activate'), 'default');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'user-activate'
            )
        )
            ->setName('userActivate')
            ->setAction($defaultUserActivateUrl)
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
            ->addValidator('NotEmpty', true)
            ->addValidator('EmailAddress')
            ->addValidator('stringLength', true, array(5, 255, 'UTF-8'))
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
                'data-title' => $this->translate('Введите пароль для своей учетной записи.'),
                'data-placement' => 'bottom'))
            ->setRequired(true)
            ->addValidator('stringLength', true, array(6, 40, 'UTF-8'))
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $activationCode = new Zend_Form_Element_Text('ActivationCode');
        $activationCode->setLabel($this->translate('Код активации'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Код активации'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $captcha = new Zend_Form_Element_Captcha('Captcha', array(
            'ignore' => true,
            // игнорируем, чтобы не получать значение элемента при вызове
            // метода getValues() нашей формы
            //'label' => 'captchaLabel',
            'captcha' => array(
                'captcha' => 'ReCaptcha',
                'pubKey' => '6LdvedYSAAAAALfZ46Sx1yYF75erQzJdkZ0OG2Kt',
                'privKey' => '6LdvedYSAAAAALTNnQNU_J4z_LYEE8A01CfFZa_D',
            ),
            'captchaOptions' => array('theme' => 'white',
                // возможны варианты 'red' | 'white'
                // | 'blackglass' | 'clean' | 'custom'
                'lang' => 'ru'),
            // здесь также возможны 'en', 'nl',
            // 'fr', 'de', 'pt', 'ru', 'es', 'tr'
            // Captcha использует свой собственный декоратор, поэтому, для корректного ее отображения
            // декоратор должен быть задан примерно следующим образом:
            'decorators' => array(
                array('Captcha'),
                array('Errors'),
            )
        ));

        $captcha->setDecorators(array(
            array('Label', array('class' => 'control-label')),
            array('Errors'),
            array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
        ));

        $csrfToken = new Zend_Form_Element_Hash('defaultUserActivateCsrfToken');
        $csrfToken->setSalt(md5(microtime() . uniqid()))
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Активировать'))
            ->setAttrib('class', 'btn btn-success btn-block btn-lg')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($email)
            ->addElement($password)
            ->addElement($activationCode);

        $this->addElement($captcha);

        $this->addElement($csrfToken);

        $this->addElement($submit);

        $this->addDisplayGroup(
            array(
                $this->getElement('Submit'),
            ), 'FormActions'
        );

        $this->getDisplayGroup('FormActions')
            ->setOrder(100)
            ->setDecorators(array(
                array('FormElements'),
                array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
            ));
    }

}
