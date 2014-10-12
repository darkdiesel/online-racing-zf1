<?php

class Peshkov_Form_Post_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $adminPostAddUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'add'), 'default');
        $adminPostAllUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'all'), 'adminPostAll');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'post-add'
            )
        )
            ->setName('postAdd')
            ->setAction($adminPostAddUrl)
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $name = new Zend_Form_Element_Text('Name');
        $name->setLabel($this->translate('Название'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Название'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $logoUrl = new Zend_Form_Element_Text('ImageUrl');
        $logoUrl->setLabel($this->translate('Картинка поста'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Вставьте ссылку на картинку сюда'))
            ->setRequired(false)
            ->addValidator('stringLength', false, array(0, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());


        //TODO: Uncoment this code for allow upload to server post image
//        $logoUrl = new Zend_Form_Element_File('ImageUrl');
//        $logoUrl->setLabel($this->translate('Картинка поста'))
//            ->setAttrib('class', 'form-control')
//            ->setRequired(true)
//            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/posts/')
//            ->addValidator('Size', false, 512000) // 500 kb
//            ->addValidator('Extension', false, 'jpg,jpeg,png,gif')
//            //->addValidator('IsImage')
//            ->addValidator('Count', false, 1)
//            ->setDecorators($this->getView()->getDecorator()->fileDecorators());

        $postCategorys = new Zend_Form_Element_Select('PostCategoryID');
        $postCategorys->setLabel($this->translate('Категория поста'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Типы постов'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getPostCategories() as $postCategory) {
            $postCategorys->addMultiOption($postCategory['ID'], $postCategory['Name']);
        };

        $contentTypes = new Zend_Form_Element_Select('ContentTypeID');
        $contentTypes->setLabel($this->translate('Тип контента'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Типы контента'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getContentTypes() as $contentType) {
            $contentTypes->addMultiOption($contentType['ID'], $contentType['Name']);
            if ($contentType['Name'] == 'full html') {
                $contentTypes->setValue($contentType['ID']);
            }
        };

        $preview = new Zend_Form_Element_Textarea('Preview');
        $preview->setLabel($this->translate('Анонс'))
            ->setOptions(array('maxLength' => 500, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Анонс'))
            ->setRequired(true)
            ->addValidator('stringLength', false, array(0, 500, 'UTF-8'))
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $text = new Zend_Form_Element_Textarea('Text');
        $text->setLabel($this->translate('Текст'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Текст'))
            ->setRequired(true)
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $publish = new Zend_Form_Element_Checkbox('Publish');
        $publish->setLabel($this->translate('Опубликовать?'))
            ->setValue(1)
            ->setDecorators($this->getView()->getDecorator()->checkboxDecorators());

        $publishToSlider = new Zend_Form_Element_Checkbox('PublishToSlider');
        $publishToSlider->setLabel($this->translate('Опубликовать в слайдер? (Не работает)'))
            ->setValue(0)
            ->setDecorators($this->getView()->getDecorator()->checkboxDecorators());

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

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='" . $adminPostAllUrl . "'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($logoUrl)
            ->addElement($preview)
            ->addElement($text);

        $this->addElement($postCategorys)
            ->addElement($contentTypes);

        $this->addElement($publish)
            ->addElement($publishToSlider);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('ImageUrl'),
                $this->getElement('Preview'),
                $this->getElement('Text')
            ), 'PostInfo'
        );

        $this->getDisplayGroup('PostInfo')
            ->setOrder(10)
            ->setLegend('Информация о посте')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('PostCategoryID'),
                $this->getElement('ContentTypeID'),
            ), 'PostSettings'
        );

        $this->getDisplayGroup('PostSettings')
            ->setOrder(20)
            ->setLegend('Настройки поста')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('Publish'),
                $this->getElement('PublishToSlider'),
            ), 'PostPublishSettings'
        );

        $this->getDisplayGroup('PostPublishSettings')
            ->setOrder(30)
            ->setLegend('Настройки публикации')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

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

    private function getPostCategories()
    {
        $query = Doctrine_Query::create()
            ->from('Default_Model_PostCategory pt')
            ->orderBy('pt.ID ASC');

        return $query->fetchArray();
    }

    private function getContentTypes()
    {
        $query = Doctrine_Query::create()
            ->from('Default_Model_ContentType ct')
            ->orderBy('ct.ID ASC');

        return $query->fetchArray();
    }

}
