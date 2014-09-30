<?php

class Peshkov_Form_User_RestorePass extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $defaultUserRestorePassUrl = $this->getView()->url(array('module' => 'default', 'controller' => 'user', 'action' => 'restore-pass'), 'default');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'user-restore-pass'
            )
        )
            ->setName('userRestorePass')
            ->setAction($defaultUserRestorePassUrl)
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

        $csrfToken = new Zend_Form_Element_Hash('defaultUserRestorePassCsrfToken');
        $csrfToken->setSalt(md5(microtime() . uniqid()))
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Восстановить'))
            ->setAttrib('class', 'btn btn-success btn-block btn-lg')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($email);

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
