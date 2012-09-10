<?php

	class Application_Form_UserLoginForm extends Zend_Form
    {
		public function init()
		{
			// Set the method for the display form to POST
			$this->setMethod('post');
			$this->setName('userlogin');
			$isEmptyMessage = 'Значение является обязательным и не может быть пустым';
			
			// Add an email element
			$this->addElement('text', 'email', array(
				'label'      => 'Ваш e-mail адресс:',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StringTrim'),
				'validators' => array(
					'EmailAddress',
				)
			));

			$this->addElement('password', 'password', array(
				'label'      => 'Ваш пароль:',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StringTrim'),
				
			));
				
			$this->addElement( 'submit', 'submit', array(
				'ignore' => true,
                'class' => 'btn btn-primary',
				'label' => 'Войти',
			));
		}
    }