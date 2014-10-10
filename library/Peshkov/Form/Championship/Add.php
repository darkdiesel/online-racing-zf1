<?php

class Peshkov_Form_Championship_Add extends Zend_Form
{

    protected function translate($str)
    {
        $translate = new Zend_View_Helper_Translate();
        $lang = Zend_Registry::get('Zend_Locale');
        return $translate->translate($str, $lang);
    }

    public function init()
    {
        $adminChampionshipAddUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'add'), 'default');
        $adminChampionshipAllUrl = $this->getView()->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'all'), 'adminChampionshipAll');

        $this->setAttribs(
            array(
                'class' => 'block-form block-form-default',
                'id' => 'championship-add'
            )
        )
            ->setName('championshipAdd')
            ->setAction($adminChampionshipAddUrl)
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
                    'table' => 'championship',
                    'field' => 'Name',
                )
            )
            //->addValidator(new App_Validate_NoDbRecordExists('country', 'NativeName'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $leagues = new Zend_Form_Element_Select('LeagueID');
        $leagues->setLabel($this->translate('Лига'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Лига'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getLeagues() as $league) {
            $leagues->addMultiOption($league['ID'], $league['Name']);
        };

        $racingSeries = new Zend_Form_Element_Select('RacingSeriesID');
        $racingSeries->setLabel($this->translate('Гоночная серия'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Гоночная серия'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getRacingSeries() as $racingSerie) {
            $racingSeries->addMultiOption($racingSerie['ID'], $racingSerie['Name']);
        };

        $users = new Zend_Form_Element_Select('UserID');
        $users->setLabel($this->translate('Администратор чемпионата'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Администратор чемпионата'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getUsers() as $user) {
            $users->addMultiOption($user['ID'], $user['LastName'] . ' ' . $user['FirstName'] . ' (' . $user['NickName'] . ')');
        };

        $postRuleID = new Zend_Form_Element_Select('PostRuleID');
        $postRuleID->setLabel($this->translate('Регламент'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Регламент'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getPostRules() as $post) {
            $postRuleID->addMultiOption($post['ID'], $post['Name']);
        };

        $postGameID = new Zend_Form_Element_Select('PostGameID');
        $postGameID->setLabel($this->translate('Игра'))
            ->setOptions(array('class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Игра'))
            ->setRequired(true)
            ->addFilter('HtmlEntities')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());
        foreach ($this->getPostGames() as $post) {
            $postGameID->addMultiOption($post['ID'], $post['Name']);
        };

        $logoUrl = new Zend_Form_Element_File('LogoUrl');
        $logoUrl->setLabel($this->translate('Логотип чемпионата'))
            ->setAttrib('class', 'form-control')
            ->setRequired(true)
            ->setDestination(APPLICATION_PATH . '/../public_html/data-content/data-uploads/championships/')
            ->addValidator('Size', false, 512000) // 500 kb
            ->addValidator('Extension', false, 'jpg,jpeg,png,gif')
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

        $dateStart = new Zend_Form_Element_Text('DateStart');
        $dateStart->setLabel($this->translate('Дата старта чемпионата'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Дата старта чемпионата'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $dateEnd = new Zend_Form_Element_Text('DateEnd');
        $dateEnd->setLabel($this->translate('Дата окончания чемпионата'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('Дата окончания чемпионата'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
            ->addFilter('StringTrim')
            ->setDecorators($this->getView()->getDecorator()->elementDecorators());

        $hotLapsIP = new Zend_Form_Element_Text('HotLapsIP');
        $hotLapsIP->setLabel($this->translate('IP сервиса HotLaps'))
            ->setOptions(array('maxLength' => 255, 'class' => 'form-control'))
            ->setAttrib('placeholder', $this->translate('IP сервиса HotLaps'))
            ->setRequired(false)
            ->addValidator('NotEmpty')
            ->addValidator('stringLength', false, array(1, 255, 'UTF-8'))
            ->addFilter('StripTags')
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

        $cancel = new Zend_Form_Element_Button('Cancel');
        $cancel->setLabel($this->translate('Отмена'))
            ->setAttrib('onClick', "location.href='".$adminChampionshipAllUrl."'")
            ->setAttrib('class', 'btn btn-danger')
            ->setIgnore(true)
            ->setDecorators($this->getView()->getDecorator()->buttonDecorators());

        $this->addElement($name)
            ->addElement($leagues)
            ->addElement($racingSeries)
            ->addElement($users)
            ->addElement($logoUrl)
            ->addElement($postRuleID)
            ->addElement($postGameID)
            ->addElement($description);

        $this->addElement($dateStart)
            ->addElement($dateEnd)
            ->addElement($hotLapsIP);

        $this->addElement($submit)
            ->addElement($reset)
            ->addElement($cancel);

        $this->addDisplayGroup(
            array(
                $this->getElement('Name'),
                $this->getElement('LogoUrl'),
                $this->getElement('Description'),
            ), 'ChampionshipInfo'
        );

        $this->getDisplayGroup('ChampionshipInfo')
            ->setOrder(10)
            ->setLegend('Информация о чемпионате')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('LeagueID'),
                $this->getElement('RacingSeriesID'),
                $this->getElement('UserID'),
                $this->getElement('PostRuleID'),
                $this->getElement('PostGameID'),
            ), 'ChampionshipSettings'
        );

        $this->getDisplayGroup('ChampionshipSettings')
            ->setOrder(20)
            ->setLegend('Настройки чемпионата')
            ->setDecorators($this->getView()->getDecorator()->displayGroupDecorators());

        $this->addDisplayGroup(
            array(
                $this->getElement('DateStart'),
                $this->getElement('DateEnd'),
                $this->getElement('HotLapsIP'),
            ), 'AdditionalChampionshipInfo'
        );

        $this->getDisplayGroup('AdditionalChampionshipInfo')
            ->setOrder(30)
            ->setLegend('Дополнительная Информация о чемпионате')
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

    public function getLeagues(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_League l')
            ->leftJoin('l.User u')
            ->orderBy('l.Name ASC');
        return $query->fetchArray();
    }

    public function getRacingSeries(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_RacingSeries rs')
            ->orderBy('rs.Name ASC');
        return $query->fetchArray();
    }

    public function getUsers(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_User u')
            ->leftJoin('u.UserRole ur')
            ->leftJoin('ur.Role r')
            ->where('r.SystemName = ?', 'admin')
            ->orWhere('r.SystemName = ?', 'super_admin')
            ->orWhere('r.SystemName = ?', 'master')
            ->orderBy('u.LastName ASC');
        return $query->fetchArray();
    }

    public function getPostRules(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_Post p')
            ->leftJoin('p.PostCategory pc')
            ->leftJoin('p.ContentType ct')
            ->where('pc.SystemName = ?', 'rule')
            ->orderBy('p.Name ASC');
        return $query->fetchArray();
    }

    public function getPostGames(){
        $query = Doctrine_Query::create()
            ->from('Default_Model_Post p')
            ->leftJoin('p.PostCategory pc')
            ->leftJoin('p.ContentType ct')
            ->where('pc.SystemName = ?', 'game')
            ->orderBy('p.Name ASC');
        return $query->fetchArray();
    }

}
