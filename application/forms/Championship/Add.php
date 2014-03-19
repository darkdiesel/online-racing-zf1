<?php

class Application_Form_Championship_Add extends Zend_Form {

	protected function translate($str) {
		$translate = new Zend_View_Helper_Translate();
		$lang = Zend_Registry::get('Zend_Locale');
		return $translate->translate($str, $lang);
	}

	public function init() {
		$this->setMethod('post')
				->setName('admin-championship-add');

		$this->setAttribs(array(
			'class' => 'block-item block-item-form',
			'id' => 'admin-championship-add',
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
				new App_Validate_NoDbRecordExists('championship', 'name')
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('file', 'logo', array(
			'label' => $this->translate('Логотип чемпионата'),
			'required' => true,
			'class' => 'form-control',
			'destination' => APPLICATION_PATH . '/../public_html/data-content/data-uploads/championship/logo',
			'validators' => array(
				array('Size', false, 5120000),
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

		// artcile type
		$this->addElement('select', 'league', array(
			'label' => $this->translate('Лига'),
			//'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
			'required' => true,
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

		// artcile type
		$this->addElement('select', 'rule', array(
			'label' => $this->translate('Регламент'),
			//'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
			'required' => true,
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

		// artcile type
		$this->addElement('select', 'game', array(
			'label' => $this->translate('Игра для проведения'),
			//'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
			'required' => true,
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
		// artcile type
		$this->addElement('select', 'admin', array(
			'label' => $this->translate('Администратор чемпионата'),
			//'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
			'required' => true,
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

		$this->addElement('text', 'date_start', array(
			'label' => $this->translate('Дата начала'),
			'placeholder' => $this->translate('Дата начала'),
			'description' => $this->translate('Формат даты yyyy-mm-dd (yyyy - год, mm - месяц, dd - день)'),
			'required' => true,
			'filters' => array('StripTags', 'StringTrim'),
			'class' => 'form-control',
			'validators' => array(
				array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
				array('StringLength', true, array('min' => 10, 'max' => 10)),
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('text', 'date_end', array(
			'label' => $this->translate('Дата окончания'),
			'placeholder' => $this->translate('Дата окончания'),
			'description' => $this->translate('Формат даты yyyy-mm-dd (yyyy - год, mm - месяц, dd - день)'),
			'required' => true,
			'filters' => array('StripTags', 'StringTrim'),
			'class' => 'form-control',
			'validators' => array(
				array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
				array('StringLength', true, array('min' => 10, 'max' => 10)),
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('text', 'hotlap_ip', array(
			'label' => $this->translate('Hot-Laps IP'),
			'placeholder' => $this->translate('Hot-Laps IP'),
			'maxlength' => 255,
			'filters' => array('StripTags', 'StringTrim'),
			'required' => false,
			'class' => 'form-control',
			'validators' => array(
			),
			'decorators' => array(
				'ViewHelper', 'HtmlTag', 'label', 'Errors',
				array('Label', array('class' => 'control-label')),
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('class' => '')),
			)
		));

		$this->addElement('textarea', 'description', array(
			'label' => $this->translate('Описание чемпионата'),
			'placeholder' => $this->translate('Описание чемпионата'),
			'cols' => 60,
			'rows' => 10,
			'class' => 'form-control',
			'maxlength' => 500,
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
				array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
			)
		));

		$this->addElement('reset', 'reset', array(
			'ignore' => true,
			'class' => 'btn btn-default',
			'label' => $this->translate('Сбросить'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
			)
		));

		$this->addElement('button', 'cancel', array(
			'ignore' => true,
			'class' => 'btn btn-default',
			'label' => $this->translate('Отмена'),
			'decorators' => array(
				'ViewHelper', 'HtmlTag',
				array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
				array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
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
			array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
		));
	}

}
