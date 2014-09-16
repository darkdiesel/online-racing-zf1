<?php

class Peshkov_Form_League_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'league-add'
            )
        )
            ->setName('leagueAdd')
            ->setAction(
                $this->getView()->url(
                    array('module' => 'admin', 'controller' => 'league', 'action' => 'add'), 'default'
                )
            )
            ->setMethod('post')
            ->addDecorators($this->getView()->getDecorator()->formDecorators());

        $name = new Zend_Form_Element_Text('Name');
        $name->setLabel($this->translate('Название'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Название'))
            ->setRequired(true)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addValidator(
                'Db_NoRecordExists', false,
                array(
                    'table' => 'league',
                    'field' => 'Name',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'NativeName'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $users = new Zend_Form_Element_Select('UserID');
        $users->setLabel($this->translate('Администратор лиги'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Администратор лиги'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getUsers() as $user) {
            $users->addMultiOption($user['ID'], $user['Surname'] . ' ' . $user['Name'] . ' (' . $user['NickName'] . ')');
        };

        $urlImageLogo = new Zend_Form_Element_File('ImageLogoUrl');
        $urlImageLogo->setLabel($this->translate('Логотип лиги'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/leagues/')
            ->addValidator('Size', false, 512000) // 500 kb
            ->addValidator('Extension', false, 'jpg,png,gif')
            //->addValidator('IsImage')
            ->addValidator('Count', false, 1)
            ->setDecorators($this->getView()->getDecorator()->fileDecorators());

        $description = new Zend_Form_Element_Textarea('Description');
        $description->setLabel($this->translate('Описание'))
            ->setOptions(array('maxLength' => 500, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Описание'))
            ->setRequired(false)
            ->addValidator('stringLength', false, array(0, 500, 'UTF-8'))
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

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

        $adminLeagueAllUrl = $this->getView()->url(
            array('module' => 'admin', 'controller' => 'league', 'action' => 'all'), 'adminLeagueAll'
        );

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='{$adminLeagueAllUrl}'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($users)
            ->addElement($urlImageLogo)
            ->addElement($description);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('UserID'),
                $this->getElement('ImageLogoUrl'),
                $this->getElement('Description')
            ), 'LeagueInfo'
        );

        $this->getDisplayGroup('LeagueInfo')
            ->setOrder(10)
            ->setLegend('Информация о лиге')
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

    public function getUsers(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_User u')
            ->leftJoin('u.UserRole ur')
            ->leftJoin('ur.Role r')
            ->where('r.name = ?', 'admin')
            ->orWhere('r.name = ?', 'super_admin')
            ->orWhere('r.name = ?', 'master')
            ->orderBy('u.surname ASC');
        return $query->fetchArray();
    }

}
