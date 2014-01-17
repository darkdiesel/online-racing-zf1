<?php

class Admin_TeamController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Команда'));
	}

	// action for view article type
	public function idAction() {
		$request = $this->getRequest();
		$team_id = (int) $request->getParam('team_id');

		$team_data = $this->db->get('team')->getItem($team_id);

		$team = new Application_Model_DbTable_Team();
		$team_data = $team->fetchRow(array('id = ?' => $team_id));

		if (count($team_data) != 0) {
			$this->view->team = $team_data;
			$this->view->headTitle($team_data->name);
			$this->view->pageTitle($team_data->name);
			return;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая команда не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не найдена!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Команда не найдена!')}");
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

	// action for add new team
	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить команду'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_Team_Add();
		$form->setAction(
				$this->view->url(
						array('module' => 'admin', 'controller' => 'team', 'action' => 'add'), 'default', true
				)
		);

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$date = date('Y-m-d H:i:s');
				$new_team_data = array(
					'name' => $form->getValue('name'),
					'description' => $form->getValue('description'),
					'date_create' => $date,
					'date_edit' => $date,
				);

				$new_team = $this->db->get('team')->createRow($new_team_data);
				$new_team->save();

				$this->redirect(
						$this->view->url(
								array('module' => 'admin', 'controller' => 'team', 'action' => 'id',
							'team_id' => $new_team->id), 'team_id', true
						)
				);
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$this->view->form = $form;
	}

	// action for edit article type
	public function editAction() {
		$request = $this->getRequest();
		$team_id = (int) $request->getParam('team_id');

		$team_data = $this->db->get('team')->getItem($team_id);

		if ($team_data) {
			// form
			$form = new Application_Form_Team_Edit();
			$form->setAction($this->view->url(
							array('module' => 'admin', 'controller' => 'team', 'action' => 'edit',
						'team_id' => $team_id), 'team_action', true
			));

			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'id', 'team_id' => $team_id), 'team_id', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$new_team_data = array(
						'name' => $form->getValue('name'),
						'description' => $form->getValue('description'),
						'date_edit' => date('Y-m-d H:i:s')
					);

					$team_where = $this->db->get('team')->getAdapter()->quoteInto('id = ?', $team_id);
					$this->db->get('team')->update($new_team_data, $team_where);

					$this->redirect($this->view->url(
									array('module' => 'admin', 'controller' => 'team', 'action' => 'id',
								'team_id' => $team_id), 'team_id', true
					));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}
			// Set Page Title and Heade Title
			$this->view->headTitle($team_data->name);
			$this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$team_data->name}");

			// Fill form fields
			$form->name->setvalue($team_data->name);
			$form->description->setvalue($team_data->description);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая команда не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не найдена!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Команда не найдена!')}");
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
