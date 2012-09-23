<?php

	class Application_Form_UserActivateForm extends Zend_Form
    {
		public function init()
		{
			// Set the method for the display form to POST
			$this->setMethod('post');
            $this->setAction('/user/activate');
			$this->setName('userActivate');
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
				)
			));

			$this->addElement('password', 'password', array(
				'label'      => 'Пароль:',
				'placeholder' => 'Пароль',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StripTags','StringTrim'),
				'validators' => array(
                                      array('StringLength', true, array('min' => 6, 'max' => 25))
                                    )
			));

			$this->addElement('text', 'confirmCode', array(
				'label'      => 'Код подтверждения:',
				'placeholder' => 'Код подтверждения',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StripTags','StringTrim'),
				'validators' => array('alnum',
			        //array('regex', false, '/^[a-z]/i')
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
				'label' => 'Подвердить',
			));

			$this->addElement( 'reset', 'reset', array(
				'ignore' => true,
				'class' => 'btn',
				'label' => 'Сбросить',
			));
		}
		
    }