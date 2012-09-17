<?php

	class Application_Form_UserLoginForm extends Zend_Form
    {
		public function init()
		{
			// Set the method for the display form to POST
			$this->setMethod('post');
            $this->setAction('/user/login');
			$this->setName('userlogin');
            $this->setAttrib('class', 'white_border');
			$isEmptyMessage = 'Значение является обязательным и не может быть пустым';
			
			// Add an email element
			$this->addElement('text', 'email', array(
				'label'      => 'E-mail:',
                'placeholder' => 'E-mail',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StringTrim','StringToLower'),
				'validators' => array(
					'EmailAddress',
				)
			));

			$this->addElement('password', 'password', array(
				'label'      => 'Пароль:',
                'placeholder' => 'Пароль',
				'required'   => true,
				'class'		 => 'x_field',
				'filters'    => array('StringTrim'),
			));

            $this->addElement('checkbox', 'remember', array(
                'label'      => 'Запомнить меня',
            ));
				
			$this->addElement( 'submit', 'submit', array(
				'ignore' => true,
                'class' => 'btn btn-primary',
				'label' => 'Войти',
			));
		}
    }