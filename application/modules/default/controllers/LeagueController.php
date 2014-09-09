<?php

class LeagueController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Лига'));
    }

    // action for view racing series
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'leagueID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'leagueID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_League l')
                ->leftJoin('l.User u')
                ->where('l.ID = ?', $requestData->leagueID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->leagueData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->translate('Лига ') . ' :: ' . $result[0]['Name']
                );

                //add breadscrumb
                $this->view->breadcrumb()->LeagueAll('1')->league($requestData->leagueID, $result[0]['Name'], $requestData->page);

                $championship = new Application_Model_DbTable_Championship();

                $page_count_items = 5;
                $page_range = 5;
                $items_order = 'DESC';
                $championships_data = $championship->getChampionshipsPagerByLeague($page_count_items, $requestData->page, $page_range, $items_order, $requestData->leagueID);

                if ($championships_data) {
                    $this->view->championships_data = $championships_data;
                } else {
                    $this->messages->addInfo($this->view->translate('В лиге не найдено чемпионатов!'));
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Лига не найдена!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Лига не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all racing series
    public function allAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'page' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'page' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $this->view->headTitle($this->view->translate('Все'));
            $this->view->pageTitle($this->view->translate('Лиги'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_League l')
                ->leftJoin('l.User u')
                ->orderBy('l.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $leaguePaginator = new Zend_Paginator($adapter);
            // pager settings
            $leaguePaginator->setItemCountPerPage("10");
            $leaguePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $leaguePaginator->setPageRange("5");

            if ($leaguePaginator->count() == 0) {
                $this->view->leagueData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->leagueData = $leaguePaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
