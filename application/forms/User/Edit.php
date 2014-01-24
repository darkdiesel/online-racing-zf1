<?php

class Application_Form_User_Edit extends Zend_Form {

	protected function translate($str) {
		$translate = new Zend_View_Helper_Translate();
		$lang = Zend_Registry::get('Zend_Locale');
		return $translate->translate($str, $lang);
	}

	public function init() {
		// Set the method for the display form to POST
		$this->setMethod('post')
				->setName('default-user-edit');

		$this->setAttribs(array(
			'class' => 'block-item block-item-form form-horizontal',
			'id' => 'default-user-edit',
		));

		$this->setEnctype('multipart/form-data');
		$this->addDecorators(array('formElements', 'form'));

		// user info
		$this->addElement('text', 'name', array(
			'label' => $this->translate('Имя'),
			'placeholder' => $this->translate('Имя'),
			'class' => "form-control",
			'maxlength' => 40,
			'filters' => array('StripTags', 'StringTrim'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'surname', array(
			'label' => $this->translate('Фамилия'),
			'placeholder' => $this->translate('Фамилия'),
			'class' => "form-control",
			'maxlength' => 40,
			'filters' => array('StripTags', 'StringTrim'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'birthday', array(
			'label' => $this->translate('Дата рождения'),
			'placeholder' => $this->translate('Дата рождения'),
			'class' => "form-control",
			'description' => $this->translate('Формат даты yyyy-mm-dd (yyyy - год, mm - месяц, dd - день)'),
			'filters' => array('StripTags', 'StringTrim'),
			'validators' => array(
				array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
			//array('StringLength', true, array('min' => 10, 'max' => 10)),
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));


		// artcile type
		$this->addElement('select', 'country', array(
			'label' => $this->translate('Страна'),
			'class' => "form-control",
			//'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
			'registerInArrayValidator' => false,
			//'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'city', array(
			'label' => $this->translate('Город'),
			'placeholder' => $this->translate('Город'),
			'class' => "form-control",
			'filters' => array('StripTags', 'StringTrim'),
			'maxlength' => 100,
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		// avatar_type
		$this->addElement('radio', 'avatar_type', array(
			'label' => $this->translate('Выбрать аватар'),
			'multiOptions' => array(
				0 => $this->translate('Без аватара'),
				1 => $this->translate('Загруженный'),
				2 => $this->translate('Ссылка с другого ресурса'),
				3 => $this->translate('Gravatar'),
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'element_label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box avatar_type ')),
				array('HtmlTag', array('class' => 'element_tag')),
			)
		));

		$this->addElement('file', 'avatar_load', array(
			'label' => $this->translate('Выбрать на компьютере'),
			//'required' => true,
			'class' => "form-control",
			'destination' => APPLICATION_PATH . '/../public_html/data-content/data-uploads/user/avatar_upload/',
			'decorators' => array(
				'File', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'element_label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
				array('HtmlTag', array('class' => 'element_tag')),
			),
			'validators' => array(
				array('Size', false, array('max' => (150 * 10240))),
				array('Extension', false, 'jpg,jpeg,png,gif'),
				array('Count', false, 1)
			)
		));

		// avatar_link
		$this->addElement('text', 'avatar_link', array(
			'label' => $this->translate('Ссылка с другого ресурса'),
			'placeholder' => $this->translate('Ссылка с другого ресурса'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'description' => $this->translate('Зайди на другой сайт, скопируйте ссылку понравившейся картинки и вставте в это поле.'),
			'class' => "form-control",
			'validators' => array(
				array('StringLength', true, array('min' => 5, 'max' => 255))
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors', 'Description',
				array('Label', array('class' => 'element_label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
				array('HtmlTag', array('class' => 'element_tag')),
			)
		));

		// gravatar
		$this->addElement('text', 'avatar_gravatar_email', array(
			'label' => $this->translate('Gravatar'),
			'placeholder' => $this->translate('Gravatar'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'description' => $this->translate('Зарегестрируйтесь в сервисе Gravatar (http://Gravatar.com) и введите регистрационный e-mail в поле выше.'),
			'class' => "form-control",
			'validators' => array(
				'EmailAddress',
				array('StringLength', true, array('min' => 5, 'max' => 255))
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors', 'Description',
				array('Label', array('class' => 'element_label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
				array('HtmlTag', array('class' => 'element_tag')),
			)
		));

		//contacts information
		$this->addElement('text', 'skype', array(
			'label' => $this->translate('Skype'),
			'placeholder' => $this->translate('Skype'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'icq', array(
			'label' => $this->translate('ICQ'),
			'placeholder' => $this->translate('ICQ'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'gtalk', array(
			'label' => $this->translate('Google Talk'),
			'placeholder' => $this->translate('Google Talk', 'StringToLower'),
			'filters' => array('StripTags', 'StringTrim'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'www', array(
			'label' => $this->translate('www'),
			'placeholder' => $this->translate('www', 'StringToLower'),
			'filters' => array('StripTags', 'StringTrim'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		// social networks
		$this->addElement('text', 'vk', array(
			'label' => $this->translate('Вконтакте'),
			'placeholder' => $this->translate('Вконтакте'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'fb', array(
			'label' => $this->translate('Facebook'),
			'placeholder' => $this->translate('Facebook'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'tw', array(
			'label' => $this->translate('Twitter'),
			'placeholder' => $this->translate('Twitter'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		$this->addElement('text', 'gp', array(
			'label' => $this->translate('Google+'),
			'placeholder' => $this->translate('Google+'),
			'filters' => array('StripTags', 'StringTrim', 'StringToLower'),
			'class' => "form-control",
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		// additional information
		$this->addElement('textarea', 'about', array(
			'label' => $this->translate('О себе'),
			'placeholder' => $this->translate('О себе') . ' ...',
			'maxlength' => 500,
			'class' => "form-control",
			'filters' => array('StripTags', 'StringTrim'),
			'validators' => array(
				array('validator' => 'StringLength', 'options' => array(0, 500))
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label col-lg-2')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => 'col-lg-10')),
			)
		));

		// element for saving tab name
		$this->addElement('hidden', 'tab_name', array(
			'value' => '',
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array('HtmlTag', array('class' => 'tab_name hidden_group'))
			)
		));

		// conrols
		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'class' => 'btn btn-primary',
			'label' => $this->translate('Сохранить'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'block-center')),
			)
		));

		$this->addElement('reset', 'reset', array(
			'label' => "",
			'ignore' => true,
			'class' => 'btn btn-default',
			'label' => $this->translate('Сбросить'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'block-center')),
			)
		));

		// personal information display group
		$this->addDisplayGroup(array(
			$this->getElement('name'),
			$this->getElement('surname'),
			$this->getElement('birthday'),
			$this->getElement('country'),
			$this->getElement('city'),
				), 'personal_Inf', array('legend' => $this->translate('Личные данные')));

		$this->getDisplayGroup('personal_Inf')->setDecorators(array(
			'FormElements',
			array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
			'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'personal_Inf display_group')),
		));

		// avatar display group
		$this->addDisplayGroup(array(
			$this->getElement('avatar_type'),
			$this->getElement('avatar_load'),
			$this->getElement('avatar_link'),
			$this->getElement('avatar_gravatar_email')
				), 'avatar', array('legend' => $this->translate('Изображение профиля')));

		$this->getDisplayGroup('avatar')->setDecorators(array(
			'FormElements',
			array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
			'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'avatar display_group')),
		));

		// contacts information display group
		$this->addDisplayGroup(array(
			$this->getElement('skype'),
			$this->getElement('icq'),
			$this->getElement('gtalk'),
			$this->getElement('www')
				), 'contacts_Inf', array('legend' => $this->translate('Контактная информация')));

		$this->getDisplayGroup('contacts_Inf')->setDecorators(array(
			'FormElements',
			array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
			'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'contacts_Inf display_group')),
		));

		// social networks display group
		$this->addDisplayGroup(array(
			$this->getElement('vk'),
			$this->getElement('fb'),
			$this->getElement('tw'),
			$this->getElement('gp')
				), 'social_netwoks', array('legend' => $this->translate('Социальные сети')));

		$this->getDisplayGroup('social_netwoks')->setDecorators(array(
			'FormElements',
			array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
			'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'social_netwoks display_group')),
		));

		// additional information display group
		$this->addDisplayGroup(array(
			$this->getElement('about')
				), 'additional_Inf', array('legend' => $this->translate('Дополнительная информация')));

		$this->getDisplayGroup('additional_Inf')->setDecorators(array(
			'FormElements',
			array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fieldset_inner_form')),
			'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'additional_Inf display_group')),
		));

		// form actions display group
		$this->addDisplayGroup(array(
			$this->getElement('submit'),
			$this->getElement('reset')
				), 'form_actions', array());

		$this->getDisplayGroup('form_actions')->setDecorators(array(
			'FormElements',
			//array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
			//'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
		));
	}

}
