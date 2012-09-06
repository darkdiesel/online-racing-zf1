<?php

	class Application_Form_UserRegistrationForm extends Zend_Form
    {
		public function init()
		{
			// Set the method for the display form to POST
			$this->setMethod('post');
			
			// Add an email element
			$this->addElement('text', 'email', array(
				'label'      => 'Your email address:',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StringTrim'),
				'validators' => array(
					'EmailAddress',
				)
			));
			
			$this->addElement('captcha', 'captcha', array(
				'label'      => 'Please enter the 5 letters displayed below:',
				'required'   => true,
				'class'		 => 'x_field',
				'captcha'    => array(
					'captcha' => 'Figlet',
					'wordLen' => 5,
					'timeout' => 300
				)
			));
			
			$this->addElement( 'submit', 'submit', array(
				'ignore' => true,
				'label' => 'Зарегестрироваться',
			));
		}
		
    }