<?php

class LeagueController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/league.css"));
    }

    public function idAction() {
        $request = $this->getRequest();
        $league_id = (int) $request->getParam('id');

        $mapper = new Application_Model_LeagueMapper();
        $league_data = $mapper->getLeagueDataById($league_id, 'view');

        if ($league_data == 'null') {
            $this->view->errMessage = $this->view->translate('Лига не существует');
            $this->view->headTitle($this->view->translate('Лига не существует'));
            return;
        } else {
            $this->view->league = $league_data;
            $this->view->headTitle($league_data->name);
        }
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить лигу'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_LeagueAddForm();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $league = new Application_Model_League($form->getValues());
                $mapper = new Application_Model_LeagueMapper();
                $mapper->save($league, 'add');

                $this->_helper->redirector('all', 'league');
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления лиги!');
            }
        }

        $this->view->form = $form;
    }

    public function editAction() {
        $request = $this->getRequest();
        $league_id = $request->getParam('id');

        // form
        $form = new Application_Form_LeagueEditForm();
        $form->setAction('/league/edit/' . $league_id);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $article = new Application_Model_League();
                $article->setId($league_id);
                $article->setName($form->getValue('name'));
                $article->setDescription($form->getValue('description'));
                $article->setLogo($form->getValue('logo'));

                $mapper = new Application_Model_LeagueMapper();
                $mapper->save($article, 'edit');

                $this->redirect($this->view->baseUrl('league/id/' . $league_id));
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для изминения лиги!');
            }
        }

        $mapper = new Application_Model_LeagueMapper();
        $league_data = $mapper->getLeagueDataById($league_id, 'edit');

        if ($league_data == 'null') {
            $this->view->errMessage = $this->view->translate('Лига не существует');
            $this->view->headTitle($this->view->translate('Лига не существует'));
            return;
        } else {
            $this->view->headTitle($this->view->translate('Редактировать') . ' → ' . $league_data->name);

            $form->name->setvalue($league_data->name);
            $form->logo->setvalue($league_data->logo);
            $form->description->setvalue($league_data->description);

            $this->view->form = $form;
        }
    }

    public function deleteAction() {
        // action body
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Лиги'));

        $request = $this->getRequest();
        $mapper = new Application_Model_LeagueMapper();
        $this->view->paginator = $mapper->getLeaguesPager(10, $request->getParam('page'), 5, 'all', 'ASC');
    }

}