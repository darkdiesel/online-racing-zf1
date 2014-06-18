<?php

class Application_Form_Comment_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $this->setMethod('post')
            ->setName('default-comment-add');

        $this->setAttribs(
            array(
                 'class' => 'block-item block-item-form',
                 'id'    => 'default-comment-add',
            )
        );

        // decorators for this form
        $this->addDecorators(array('formElements', 'form'));

        $this->addElement(
            'text', 'title', array(
                                  'label'       => $this->translate('Заголовок комментария'),
                                  'placeholder' => $this->translate('Заголовок комментария'),
                                  'maxlength'   => 255,
                                  'filters'     => array('StripTags', 'StringTrim'),
                                  'required'    => true,
                                  'class'       => 'form-control',
                                  'validators'  => array(),
                                  'decorators'  => array(
                                      'ViewHelper', 'HtmlTag', 'label', 'Errors',
                                      array('Label', array('class' => 'control-label')),
                                      array(array('elementDiv' => 'HtmlTag'),
                                            array('tag' => 'div', 'class' => 'form-group')),
                                      array('HtmlTag', array('class' => '')),
                                  )
                             )
        );

        $this->addElement(
            'textarea', 'content', array(
                                     'label'       => $this->translate('Текст комментария'),
                                     'placeholder' => $this->translate('Текст комментария'),
                                     'cols'        => 60,
                                     'rows'        => 10,
                                     'class'       => 'form-control',
                                     'maxlength'   => 50000,
                                     'required'    => true,
                                     'filters'     => array('StripTags', 'StringTrim'),
                                     'decorators'  => array(
                                         'ViewHelper', 'HtmlTag', 'label', 'Errors',
                                         array('Label', array('class' => 'control-label')),
                                         array(array('elementDiv' => 'HtmlTag'),
                                               array('tag' => 'div', 'class' => 'form-group')),
                                         array('HtmlTag', array('class' => '')),
                                     )
                                )
        );

        $this->addElement(
            'submit', 'submit', array(
                                     'ignore'     => true,
                                     'class'      => 'btn btn-primary',
                                     'label'      => $this->translate('Добавить'),
                                     'decorators' => array(
                                         'ViewHelper', 'HtmlTag',
                                         array(array('elementDiv' => 'HtmlTag'),
                                               array('tag' => 'div', 'class' => 'form-group')),
                                         array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
                                     )
                                )
        );

        $this->addElement(
            'reset', 'reset', array(
                                   'ignore'     => true,
                                   'class'      => 'btn btn-default',
                                   'label'      => $this->translate('Сбросить'),
                                   'decorators' => array(
                                       'ViewHelper', 'HtmlTag',
                                       array(array('elementDiv' => 'HtmlTag'),
                                             array('tag' => 'div', 'class' => 'form-group')),
                                       array('HtmlTag', array('tag' => 'span', 'class' => 'center-block')),
                                   )
                              )
        );

        $this->addDisplayGroup(
            array(
                 $this->getElement('submit'),
                 $this->getElement('reset'),
            ), 'form_actions', array()
        );

        $this->getDisplayGroup('form_actions')->setDecorators(
            array(
                 'FormElements',
                 //array(array('innerHtmlTag' => 'HtmlTag'), array('tag' => 'div')),
                 //'Fieldset',
                 array(array('outerHtmlTag' => 'HtmlTag'),
                       array('tag' => 'div', 'class' => 'block-item-form-actions text-center clearfix')),
            )
        );
    }

}
