<?php

class Application_Form_UserRestorePasswdForm extends Zend_Form
{
    public function init()
    {
        // loading validators
        Zend_Loader::loadClass('App_Validate_EqualInputs');
        Zend_Loader::loadClass('App_Validate_NoDbRecordExists');
        
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction('/user/restorepasswd');
        $this->setName('userRestorePasswd');
        $this->setAttrib('class', 'white_box');

        // Add an email element
        $this->addElement('text', 'email', array(
                                                'label'      => 'E-mail:',
                                                'placeholder' => 'E-mail',
                                                'required'   => true,
                                                'class'		 => 'x_field',
                                                'filters'    => array('StripTags','StringTrim','StringToLower'),
                                                'validators' => array(
                                                    'EmailAddress',
                                                    new App_Validate_NoDbRecordExists('user', 'email')
                                                )
                                           ));
        $this->addElement('text', 'confirmemail', array(
                                                'label'      => 'Подтвердите E-mail:',
                                                'placeholder' => 'E-mail',
                                                'AllowEmpty' => false,
                                                'class'		 => 'x_field',
                                                'filters'    => array('StripTags','StringTrim','StringToLower'),
                                                'validators' => array(
                                                            new App_Validate_EqualInputs('email')
                                                )
                                           ));

        $this->addElement(
            new Zend_Form_Element_Captcha('captcha', array(
                                                          'ignore'  => true, // игнорируем, чтобы не получать значение элемента при вызове
                                                          // метода getValues() нашей формы
                                                          'label'   => 'captchaLabel',
                                                          'captcha' => array(
                                                              'captcha' => 'ReCaptcha',
                                                              'pubKey'  => '6LdvedYSAAAAALfZ46Sx1yYF75erQzJdkZ0OG2Kt', // для получения ключей, нужно зарегистроваться
                                                              'privKey' => '6LdvedYSAAAAALTNnQNU_J4z_LYEE8A01CfFZa_D', // в сервисе ReCaptcha
                                                          ),
                                                          'captchaOptions'=> array( 'theme' => 'white', // возможны варианты 'red' | 'white'
                                                              // | 'blackglass' | 'clean' | 'custom'
                                                                                    'lang' => 'ru'),         // здесь также возможны 'en', 'nl',
                                                          // 'fr', 'de', 'pt', 'ru', 'es', 'tr'
                                                          // Captcha использует свой собственный декоратор, поэтому, для корректного ее отображения
                                                          // декоратор должен быть задан примерно следующим образом:
                                                          'decorators' => array(
                                                              array('Captcha'),
                                                              array('Errors'),
                                                          )
                                                     )));

        $this->addElement( 'submit', 'submit', array(
                                                    'ignore' => true,
                                                    'class' => 'btn btn-primary',
                                                    'label' => 'Востановить',
                                               ));

        $this->addElement( 'reset', 'reset', array(
                                                  'ignore' => true,
                                                  'class' => 'btn',
                                                  'label' => 'Сбросить',
                                             ));
    }
}