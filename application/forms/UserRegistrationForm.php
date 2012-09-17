<?php

	class Application_Form_UserRegistrationForm extends Zend_Form
    {
		public function init()
		{
			// Set the method for the display form to POST
			$this->setMethod('post');
            $this->setAction('/user/registration');
			$this->setName('userRegistration');
            $this->setAttrib('class', 'white_border');
			
			$this->addElement('text', 'login', array(
				'label'      => 'Логин:',
				'required'   => true,
				'class'		 => 'x_field',
                'filters'    => array('StringTrim','StringToLower'),
				'validators' => array('alnum',
			        array('regex', false, '/^[a-z]/i')
			    )
			));

			$this->addElement('text', 'email', array(
				'label'      => 'E-mail:',
				'required'   => true,
				'class'		 => 'x_field',
                'filters'    => array('StringTrim','StringToLower'),
				'validators' => array(
					'EmailAddress',
				)
			));

			$this->addElement('password', 'password', array(
				'label'      => 'Пароль:',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StringTrim'),
			));
			/*
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
				    'captchaOptions'=> array( 'theme' => 'clean', // возможны варианты 'red' | 'white'  
				                                                  // | 'blackglass' | 'clean' | 'custom'
				                              'lang' => 'ru'),         // здесь также возможны 'en', 'nl', 
			                                                         // 'fr', 'de', 'pt', 'ru', 'es', 'tr'

			// Captcha использует свой собственный декоратор, поэтому, для корректного ее отображения 
			// декоратор должен быть задан примерно следующим образом:
			      'decorators' => array(
			           array('Captcha'),
			           array('Errors'),
			      )
			)));*/
			
			$this->addElement( 'submit', 'submit', array(
				'ignore' => true,
				'class' => 'btn btn-primary',
				'label' => 'Зарегестрировать',
			));
		}
		
    }