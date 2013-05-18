<?php

class Application_Form_Track_Edit extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/track/edit');
        $this->setName('track');
        $this->setAttrib('class', 'white_box');

        $this->addElement('text', 'name', array(
            'label' => $this->translate('Название'),
            'placeholder' => $this->translate('Название'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim', new App_Filter_Upper()),
            'required' => true,
            'class' => 'x_field white_box_el_size_m',
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

        $this->addElement('file', 'track_scheme', array(
            'label' => $this->translate('Схема трассы (32х24)'),
            'required' => true,
            'destination' => APPLICATION_PATH . '/../public_html/img/data/track_schemes/',
            'decorators' => array(
                'File', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            ),
            'validators' => array(
                array('Size', false, 1024*500),
                array('Extension', false, 'jpg,png,gif'),
                array('Count', false, 1)
            )
        ));
        
        $this->addElement('text', 'city', array(
            'label' => $this->translate('Город'),
            'placeholder' => $this->translate('Город'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim', new App_Filter_Upper()),
            'required' => true,
            'class' => 'x_field white_box_el_size_m',
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
        $this->addElement('select', 'country', array(
            'label' => $this->translate('Страна'),
            //'multiOptions' => array(1 => '1',2 => '2', 3=>'3'),
            'required' => true,
            'registerInArrayValidator' => false,
            'validators' => array('NotEmpty'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag', 'label', 'Errors',
                array('Label', array('class' => 'element_label')),
                array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element_box')),
                array('HtmlTag', array('class' => 'element_tag')),
            )
        ));

        $this->addElement('text', 'year_track', array(
            'label' => $this->translate('Год трассы'),
            'placeholder' => $this->translate('Год трассы'),
            'maxlength' => 255,
            'filters' => array('StripTags', 'StringTrim', new App_Filter_Upper()),
            'required' => true,
            'class' => 'x_field white_box_el_size_s',
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
        
        $this->addElement('textarea', 'description', array(
            'label' => $this->translate('Описание трассы'),
            'placeholder' => $this->translate('Описание трассы'),
            'cols' => 60,
            'rows' => 10,
            'class' => 'white_box_el_size_l',
            'maxlength' => 1000,
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
            'label' => $this->translate('Добавить'),
            'decorators' => array(
                'ViewHelper', 'HtmlTag',
                array('HtmlTag', array('tag' => 'div', 'class' => 'submit form_actions_group'))
            )
        ));

        $this->addElement('reset', 'reset', array(
            'ignore' => true,
            'class' => 'btn',
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

