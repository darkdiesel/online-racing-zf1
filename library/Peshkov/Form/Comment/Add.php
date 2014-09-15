<?php

class Peshkov_Form_Comment_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'comment-add'
            )
        )
            ->setName('commentAdd')
            ->setAction(
                $this->getView()->url(
                    array('module' => 'default', 'controller' => 'comment', 'action' => 'add'), 'default'
                )
            )
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $name = new Zend_Form_Element_Text('Name');
        $name->setLabel($this->translate('Название'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Название'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(0, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $text = new Zend_Form_Element_Textarea('Text');
        $text->setLabel($this->translate('Текст комментария'))
            ->setOptions(array('maxLength' => 5000, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Текст комментария'))
            ->setRequired(false)
            ->addValidator('stringLength', false, array(0, 5000, 'UTF-8'))
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $postID = new Zend_Form_Element_Hidden('PostID');
        $postID->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        $postID->setValue($request->getParam('postID'));

        $csrfToken = new Zend_Form_Element_Hash('defaultCommentAddCsrfToken');
        $csrfToken->setSalt('asdfasdfasd')
            ->setDecorators(
                array(
                    'ViewHelper', 'HtmlTag', 'label', 'Errors',
                    array('Label', array('class' => 'control-label')),
                    array(array('elementDiv' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'form-group')),
                    array('HtmlTag', array('class' => ''))));

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel($this->translate('Добавить'))
            ->setAttrib('class', 'btn btn-primary')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $reset = new Zend_Form_Element_Reset('Reset');
        $reset->setLabel($this->translate('Сбросить'))
            ->setAttrib('class', 'btn btn-default')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($text)
            ->addElement($postID);

        $this->addElement($csrfToken);

        $this->addElement($submit)
            ->addElement($reset);

        $this->addDisplayGroup(
            array(
                $this->getElement('Submit'),
                $this->getElement('Reset'),
                $this->getElement('Cancel'),
            ), 'FormActions'
        );

        $this->getDisplayGroup('FormActions')
            ->setOrder(100)
            ->setDecorators($this->getView()->getDecorator()->formActionsGroupDecorators());
    }

}
