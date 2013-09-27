<?php

class TeamController extends App_Controller_LoaderController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Команда'));
    }

    // action for view article type
    public function idAction() {
        $request = $this->getRequest();
        $team_id = (int) $request->getParam('id');

        $team = new Application_Model_DbTable_Team();
        $team_data = $team->fetchRow(array('id = ?' => $team_id));

        if (count($team_data) != 0) {
            $this->view->team = $team_data;

            $this->view->headTitle($team_data->name);
            return;
        } else {
            $this->view->errMessage = $this->view->translate('Команда не существует');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Команда не существует'));
        }
    }

    // action for view all article types
    public function allAction() {
        $this->view->headTitle($this->view->translate('Просмотреть все'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'ASC';
        $page = $this->getRequest()->getParam('page');

        $team = new Application_Model_DbTable_Team();

        $this->view->paginator = $team->getTeamsPager($page_count_items, $page, $page_range, $items_order);
    }

    // action for add new article type
    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Team_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $team_data = array(
                    'name' => $form->getValue('name'),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $team = new Application_Model_DbTable_Team();
                $newTeam = $team->createRow($team_data);
                $newTeam->save();

                $this->redirect($this->view->baseUrl('team/id/' . $newTeam->id));
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления команды!');
            }
        }

        $this->view->form = $form;
    }

    // action for edit article type
    public function editAction() {
        $this->view->headTitle($this->view->translate('Редактировать'));

        $request = $this->getRequest();
        $team_id = $request->getParam('id');

        $team = new Application_Model_DbTable_Team();
        $team_data = $team->fetchRow(array('id = ?' => $team_id));

        if (count($team_data) != 0) {
            // form
            $form = new Application_Form_Team_Edit();
            $form->setAction('/team/edit/' . $team_id);

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $team_data = array(
                        'name' => $form->getValue('name'),
                        'description' => $form->getValue('description'),
                        'date_edit' => date('Y-m-d H:i:s')
                    );

                    $team_where = $team->getAdapter()->quoteInto('id = ?', $team_id);
                    $team->update($team_data, $team_where);

                    $this->redirect($this->view->baseUrl('team/id/' . $team_id));
                } else {
                    $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для редактирования команды!');
                }
            }

            $this->view->headTitle($team_data->name);

            $form->name->setvalue($team_data->name);
            $form->description->setvalue($team_data->description);

            $this->view->form = $form;
        } else {
            $this->view->errMessage = $this->view->translate('Команда не существует');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Команда не существует'));
        }
    }

    // action for delete article type
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $team_id = (int) $request->getParam('id');

        $team = new Application_Model_DbTable_Team();
        $team_data = $team->fetchRow(array('id = ?' => $team_id));

        if (count($team_data) != 0) {
            $this->view->headTitle($team_data->name);

            $form = new Application_Form_Team_Delete();
            $form->setAction('/team/delete/' . $team_id);
            $form->cancel->setAttrib('onClick', 'location.href="/team/id/' . $team_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $team_where = $team->getAdapter()->quoteInto('id = ?', $team_id);
                    $team->delete($team_where);

                    $this->_helper->redirector('all', 'team');
                } else {
                    $this->view->errMessage = $this->view->translate("Произошла неожиданноя ошибка! Пожалуйста обратитесь к нам и сообщите о ней");
                }
            }

            $this->view->form = $form;
            $this->view->team = $team_data;
        } else {
            $this->view->errMessage = $this->view->translate('Команда не существует');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Команда не существует'));
        }
    }

}