<?php

class Application_Form_ArticleAddForm extends Zend_Form {

    protected function translate($str) {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init() {
        $this->setMethod('post');
        $this->setAction('/article/add');
        $this->setName('articleAdd');
        $this->setAttrib('class', 'white_box');

        $this->addElement('text', 'title', array(
            'label' => $this->translate('Заголовок'),
            'placeholder' => $this->translate('Заголовок'),
            'required' => true,
            'class' => 'x_field',
        ));

        $this->addElement('textarea', 'comment', array(
            'label' => $this->translate('Текст статьи'),
            'placeholder' => $this->translate('Текст статьи'),
            'required' => true,
        ));

        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'class' => 'btn btn-primary',
            'label' => 'Добавить',
        ));

        $this->addElement('reset', 'reset', array(
            'ignore' => true,
            'class' => 'btn',
            'label' => 'Сбросить',
        ));
    }

}

