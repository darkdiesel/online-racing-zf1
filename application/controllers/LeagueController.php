<?php

class LeagueController extends Zend_Controller_Action {

    public function idAction() {
        // action body
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
        // action body
    }

    public function deleteAction() {
        // action body
    }

    public function allAction() {
        // action body
    }

}