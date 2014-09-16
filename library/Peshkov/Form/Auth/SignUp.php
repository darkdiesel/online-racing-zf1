<?php

class Peshkov_Form_Auth_SignUp extends Zend_Form
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

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'auth-sign-up'
            )
        )
            ->setName('authSignUp')
            ->setAction(
                $this->getView()->url(
                    array('module' => 'default', 'controller' => 'auth', 'action' => 'sign-up'), 'default'
                )
            )
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $name = new Zend_Form_Element_Text('Name');
        $name->setLabel($this->translate('Имя'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Имя'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators(array(
                array('ViewHelper'),
                //array('HtmlTag', array('tag' => 'div', 'class' => '')),
                array('Label', array('class' => 'control-label')),
                array('Errors'),
                array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
                array(array('elementColumnWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'col-xs-12 col-sm-12 col-md-6 col-lg-6')),
            ));

        $surName = new Zend_Form_Element_Text('Surname');
        $surName->setLabel($this->translate('Фамилия'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Фамилия'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators(array(
                array('ViewHelper'),
                //array('HtmlTag', array('tag' => 'div', 'class' => '')),
                array('Label', array('class' => 'control-label')),
                array('Errors'),
                array(array('elementWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
                array(array('elementColumnWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'col-xs-12 col-sm-12 col-md-6 col-lg-6')),
            ));

        $countries = new Zend_Form_Element_Select('CountryID');
        $countries->setLabel($this->translate('Страна'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Страна'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        foreach ($this->getCountries() as $country) {
            $countries->addMultiOption($country['ID'], $country['NativeName'] . ' (' . $country['EnglishName'] . ')');
        };

        $nickName = new Zend_Form_Element_Text('NickName');
        $nickName->setLabel($this->translate('Никнэйм'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Никнэйм'))
            ->setRequired(true)
            ->addValidator('NotEmpty', true)
            ->addValidator('stringLength', true, array(1, 255, 'UTF-8'))
            ->addValidator(
                'Db_NoRecordExists', false,
                array(
                    'table' => 'user',
                    'field' => 'NickName',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'NativeName'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

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
                'Db_NoRecordExists', false,
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

        $repeatPassword = new Zend_Form_Element_Password('RepeatPassword');
        $repeatPassword->setLabel($this->translate('Повторите пароль'))
            ->setOptions(array('maxLength' => 40, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Повторите пароль'))
            ->setAttribs(array(
                'data-title' => $this->translate('Повторите введенный выше пароль.'),
                'data-placement' => 'bottom'))
            ->setRequired(true)
            ->addValidator('identical', true, array('token' => 'Password'))
            ->addFilter('StringTrim')
            ->addFilter('StripTags')
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

        $csrfToken = new Zend_Form_Element_Hash('defaultAuthSignUpCsrfToken');
        $csrfToken->setSalt(md5(microtime() . uniqid()))
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Зарегестрироваться'))
            ->setAttrib('class', 'btn btn-danger btn-block btn-lg')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($surName);


        $this->addElement($countries);

        $this->addElement($nickName)
            ->addElement($email)
            ->addElement($password)
            ->addElement($repeatPassword);

        $this->addElement($captcha);

        $this->addElement($csrfToken);

        $this->addElement($submit);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('Surname'),
            ), 'UserIdencity'
        );

        $this->getDisplayGroup('UserIdencity')
            ->setOrder(10)
            ->setDecorators(
                array(
                    array('FormElements'),
                    array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'row')),
                )
            );

        $this->addDisplayGroup(
            array(
                $this->getElement('CountryID'),
                $this->getElement('NickName'),
                $this->getElement('Email'),
                $this->getElement('Password'),
                $this->getElement('RepeatPassword'),
                $this->getElement('Captcha'),
                $this->getElement('defaultAuthSignUpCsrfToken'),
            ), 'otherElements'
        );

        $this->getDisplayGroup('otherElements')
            ->setOrder(20)
            ->setDecorators(array(
                array('FormElements'),
                array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
            ));

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

    public function getCountries()
    {
        $query = Doctrine_Query::create()
            ->from('Default_Model_Country c')
            ->orderBy('c.NativeName ASC');
        return $query->fetchArray();
    }

}
