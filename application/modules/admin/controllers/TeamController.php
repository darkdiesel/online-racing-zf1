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
		$this->view->headTitle($this->view->translate('Все'));
		$this->view->pageTitle($this->view->translate('Команды'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $this->db->get("team")->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

		if (count($paginator)) {
			$this->view->paginator = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->translate('Запрашиваемые команды на сайте не найдены!')}");
		}
	}

	// action for add new team
	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить команду'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_Team_Add();
		$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'add'), 'default', true));
		
		$team_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'all'), 'team_all', true);
		$form->cancel->setAttrib('onClick', "location.href='{$team_all_url}'");

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

				$team_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'id','team_id' => $new_team->id), 'team_id', true);
				$this->redirect($team_id_url);
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
			$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'edit', 'team_id' => $team_id), 'team_action', true));
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

	// action for delete team
	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));
		$this->view->pageTitle($this->view->translate('Удалить команду'));

		$request = $this->getRequest();
		$team_id = (int) $request->getParam('team_id');

		$team_data = $this->db->get('team')->getItem($team_id);

		if ($team_data) {
			$this->view->headTitle($team_data->name);
			
			$this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить команду')} <strong>\"{$team_data->name}\"</strong> ?");

			$team_delete_url = $this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'delete', 'team_id' => $team_id), 'team_action', true);
			$team_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'id', 'team_id' => $team_id), 'team_id', true);

			$form = new Application_Form_Team_Delete();
			$form->setAction($team_delete_url);
			$form->cancel->setAttrib('onClick', "location.href='{$team_id_url}'");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$team_where = $this->db->get('team')->getAdapter()->quoteInto('id = ?', $team_id);
					$this->db->get('team')->delete($team_where);

					$this->messages->clearMessages();
					$this->messages->addSuccess("{$this->view->translate("Команда <strong>\"{$team_data->name} \"</strong> успешно удалена")}");

					$team_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'team', 'action' => 'all', 'page' => 1), 'team_all', true);
					$this->redirect($team_all_url);
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->form = $form;
			$this->view->team = $team_data;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая команда не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Команда не найдена!')}");
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

}
