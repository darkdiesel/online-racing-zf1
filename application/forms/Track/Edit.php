<?php

class Application_Form_Track_Edit extends Zend_Form {

	protected function translate($str) {
		$translate = new Zend_View_Helper_Translate();
		$lang = Zend_Registry::get('Zend_Locale');
		return $translate->translate($str, $lang);
	}

	public function init() {
		$this->setMethod('post')
				->setName('admin-track-edit');

		$this->setAttribs(array(
			'class' => 'block-item block-item-form',
			'id' => 'admin-track-edit',
		));

		// decorators for this form
		$this->addDecorators(array('formElements', 'form'));

		$this->addElement('text', 'name', array(
			'label' => $this->translate('Название'),
			'placeholder' => $this->translate('Название'),
			'maxlength' => 255,
			'filters' => array('StripTags', 'StringTrim'),
			'required' => true,
			'class' => 'form-control',
			'validators' => array(
				'NotEmpty',
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('text', 'track_year', array(
			'label' => $this->translate('Год трассы (ГГГГ)'),
			'placeholder' => $this->translate('Год трассы  (ГГГГ)'),
			'maxlength' => 4,
			'filters' => array('StripTags', 'StringTrim'),
			'required' => true,
			'class' => 'form-control',
			'validators' => array(
				'NotEmpty',
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('text', 'track_length', array(
			'label' => $this->translate('Длинна трассы (Км)'),
			'placeholder' => $this->translate('Длинна трассы (Км)'),
			'maxlength' => 4,
			'filters' => array('StripTags', 'StringTrim'),
			'required' => false,
			'class' => 'form-control',
			'validators' => array(
				'NotEmpty',
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('file', 'track_logo', array(
			'label' => $this->translate('Логотип трассы (32х24)'),
			'required' => false,
			'class' => 'form-control',
			'destination' => APPLICATION_PATH . '/../public_html/data-content/data-uploads/track/logos/',
			'validators' => array(
				array('Size', false, 1024 * 500),
				array('Extension', false, 'jpg,png,gif'),
				array('Count', false, 1)
			),
			'decorators' => array(
				'File', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('file', 'track_scheme', array(
			'label' => $this->translate('Схема трассы (32х24)'),
			'required' => false,
			'class' => 'form-control',
			'destination' => APPLICATION_PATH . '/../public_html/data-content/data-uploads/track/schemes/',
			'validators' => array(
				array('Size', false, 1024 * 500),
				array('Extension', false, 'jpg,png,gif'),
				array('Count', false, 1)
			),
			'decorators' => array(
				'File', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		// list of countries
		$this->addElement('select', 'city', array(
			'label' => $this->translate('Город'),
			'required' => false,
			'class' => 'form-control',
			'registerInArrayValidator' => false,
			'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		// list of countries
		$this->addElement('select', 'country', array(
			'label' => $this->translate('Страна'),
			'required' => false,
			'class' => 'form-control',
			'registerInArrayValidator' => false,
			'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('textarea', 'description', array(
			'label' => $this->translate('Описание трассы'),
			'placeholder' => $this->translate('Описание трассы'),
			'cols' => 60,
			'rows' => 10,
			'maxlength' => 65535,
			'class' => 'form-control',
			'required' => false,
			'filters' => array('StringTrim'),
			'validators' => array('NotEmpty'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'class' => 'btn btn-primary',
			'label' => $this->translate('Добавить'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'block-align-center')),
			)
		));

		$this->addElement('reset', 'reset', array(
			'ignore' => true,
			'class' => 'btn btn-default',
			'label' => $this->translate('Сбросить'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'block-align-center')),
			)
		));

		$this->addElement('button', 'cancel', array(
			'ignore' => true,
			'class' => 'btn btn-default',
			'onClick' => "location.href='/admin/track/all'",
			'label' => $this->translate('Отмена'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'block-align-center')),
			)
		));

		$this->addDisplayGroup(array(
			$this->getElement('submit'),
			$this->getElement('reset'),
			$this->getElement('cancel'),
				), 'form_actions', array());

		$this->getDisplayGroup('form_actions')->setDecorators(array(
			'FormElements',
			//array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
			//'Fieldset',
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'block-item-form-actions text-align-center clearfix')),
		));
	}

}
