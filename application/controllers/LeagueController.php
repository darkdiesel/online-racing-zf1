<?php

class LeagueController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/league.css"));
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/articles.css"));
    }

    public function idAction() {
        $request = $this->getRequest();
        $league_id = (int) $request->getParam('id');

        $league = new Application_Model_DbTable_League();

        $league_data = $league->fetchRow(array('id = ?' => $league_id));

        if (count($league_data) != 0) {
            $this->view->league = $league_data;
            $this->view->headTitle($league_data->name);
        } else {
            $this->view->errMessage = $this->view->translate('Лига не существует');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Лига не существует'));
        }
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить лигу'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_League_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $league_data = array(
                    'name' => $form->getValue('name'),
                    'logo' => $form->getValue('logo'),
                    'description' => $form->getValue('description'),
                    'user_id' => $form->getValue('admin'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $league = new Application_Model_DbTable_League();
                $newLeague = $league->createRow($league_data);
                $newLeague->save();

                $this->redirect('/league/id/' . $newLeague->id);
            } else {
                $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для добавления лиги!');
            }
        }

        $user_role = new Application_Model_DbTable_UserRole();
        $admin_role_id = $user_role->get_id('admin');

        if ($admin_role_id) {
            $user = new Application_Model_DbTable_User();

            $users_data = $user->getUserByRole($admin_role_id);

            if ($users_data) {
                foreach ($users_data as $user) {
                    $form->admin->addMultiOption($user->id, $user->login . ' (' . $user->name . '' . $user->surname . ')');
                }
                $this->view->form = $form;
            } else {
                $this->view->errMessage .= $this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы создать лигу.');
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Роль администратора на сайте не найдена! Создайте роль администратора и самого администратора, чтобы создать лигу.');
        }
    }

    public function editAction() {
        $request = $this->getRequest();
        $league_id = $request->getParam('id');

        $league = new Application_Model_DbTable_League();
        $league_data = $league->fetchRow(array('id = ?' => $league_id));

        if (count($league_data) != 0) {
            $this->view->headTitle($this->view->translate('Редактировать'));
            $this->view->headTitle($league_data->name);

            // form
            $form = new Application_Form_League_Edit();
            $form->setAction('/league/edit/' . $league_id);
            $form->cancel->setAttrib('onClick', 'location.href="/league/id/' . $league_id . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    $league_data = array(
                        'name' => $form->getValue('name'),
                        'description' => $form->getValue('description'),
                        'logo' => $form->getValue('logo'),
                        'user_id' => $form->getValue('admin'),
                        'date_edit' => date('Y-m-d H:i:s'),
                    );

                    $league_where = $league->getAdapter()->quoteInto('id = ?', $league_id);
                    $league->update($league_data, $league_where);

                    $this->redirect($this->view->baseUrl('league/id/' . $league_id));
                } else {
                    $this->view->errMessage .= $this->view->translate('Исправте следующие ошибки для изминения лиги!');
                }
            }

            $user_role = new Application_Model_DbTable_UserRole();
            $admin_role_id = $user_role->get_id('admin');

            if ($admin_role_id) {
                $user = new Application_Model_DbTable_User();

                $users_data = $user->getUserByRole($admin_role_id);

                if ($users_data) {
                    foreach ($users_data as $user) {
                        $form->admin->addMultiOption($user->id, $user->login . ' (' . $user->name . '' . $user->surname . ')');
                    }
                    $this->view->form = $form;
                } else {
                    $this->view->errMessage .= $this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы редактировать лигу.');
                }
            } else {
                $this->view->errMessage .= $this->view->translate('Роль администратора на сайте не найдена! Создайте роль администратора и самого администратора, чтобы редактировать лигу.');
            }

            $form->name->setvalue($league_data->name);
            $form->logo->setvalue($league_data->logo);
            $form->description->setvalue($league_data->description);
            $form->admin->setvalue($league_data->user_id);

            $this->view->form = $form;
        } else {
            $this->view->errMessage = $this->view->translate('Лига не существует');
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Лига не существует'));
        }
    }

    public function deleteAction() {
        // action body
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Лиги'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'ASC';
        $page = $this->getRequest()->getParam('page');

        $league = new Application_Model_DbTable_League();
        $this->view->paginator = $league->getLeaguePager($page_count_items, $page, $page_range, $items_order);
    }

}