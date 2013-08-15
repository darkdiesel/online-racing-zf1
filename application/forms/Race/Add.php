<?php

class Application_Form_Race_Add extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/race/add');
        $this->setName('raceAdd');
        $this->setAttrib('class', 'white_box white_box_size_xl');

        $this->addElement('text', 'name', array(
            'label' => $this->translate('Название'),
            'placeholder' => $this->translate('Название'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => true,
            'class' => 'x_field white_box_el_size_l',
            'validators' => array(
                'NotEmpty',
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
        
        $this->addElement('text', 'number_race', array(
            'label' => $this->translate('Номер гонки'),
            'placeholder' => $this->translate('Номер гонки'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim'),
            'required' => true,
            'class' => 'x_field white_box_el_size_x',
            'validators' => array(
                'NotEmpty',
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // artcile type
        $this->addElement('select', 'championship', array(
            'label' => $this->translate('Чемпионат'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'class' => 'white_box_el_size_l',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));
        
        $this->addElement('text', 'date_race', array(
            'label' => $this->translate('Дата гонки'),
            'placeholder' => $this->translate('Дата гонки'),
            'description' => $this->translate('Формат даты должен быть yyyy-mm-dd hh:mm:ss') . '. ' . $this->translate('Пример') . ': ' . date('Y-m-d H:i:s'),
            'title' => $this->translate('Формат даты yyyy-mm-dd hh:mm:ss (yyyy - год, mm - месяц, dd - день, hh - часы (24), mm - минуты, ss - секунды)'),
            'required' => true,
            'filters' => array('StripTags', 'StringTrim'),
            'class' => 'x_field tooltip_field',
            'validators' => array(
                //array('regex', false, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'),
                array('StringLength', true, array('min' => 19, 'max' => 19)),
            ),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors', 'description',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        // artcile type
        $this->addElement('select', 'track', array(
            'label' => $this->translate('Трасса для гонки'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'class' => 'white_box_el_size_m',
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('textarea', 'description', array(
            'label' => $this->translate('Описание гонки'),
            'placeholder' => $this->translate('Описание гонки'),
            'cols' => 60,
            'rows' => 10,
            'class' => 'white_box_el_size_xl',
            'maxlength' => 500,
            'required' => false,
            'filters' => array('StringTrim'),
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'aboutTextArea_Label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box textTextArea_box')),
            )
        ));

        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => $this->translate('Создать'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('reset', 'reset', array(
            'ignore' => true,
            'class' => 'btn btn-default',
            'label' => $this->translate('Сбросить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'reset form_actions_group'))
            )
        ));

        $this->addDisplayGroup(array(
            $this->getElement('submit'),
            $this->getElement('reset')
                ), 'form_actions', array());

        $this->getDisplayGroup('form_actions')->setDecorators(array(
            'FormElements',
            array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
            'Fieldset',
            array(array('outerHtmlTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form_actions display_group')),
        ));
    }

}

