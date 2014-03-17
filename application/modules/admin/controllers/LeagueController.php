<?php

class Admin_LeagueController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Тип статьи'));
	}

	// action for view all posts
	public function allAction() {
		$this->view->headTitle($this->view->translate('Лиги Портала'));
		$this->view->pageTitle($this->view->translate('Лиги Портала'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$league_data = $this->db->get('league')->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

		if (count($league_data)) {
			$this->view->league_data = $league_data;
		} else {
			$this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найдены!'));
		}
	}

	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить лигу'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_League_Add();

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {

				$league_data = array();

				if ($form->getValue('logo')) {
					if ($form->logo->receive()) {
						$file = $form->logo->getFileInfo();
						$ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
						$newName = Date('Y-m-d_H-i-s') . strtolower('_league_logo' . '.' . $ext);

						$filterRename = new Zend_Filter_File_Rename(array('target'
							=> $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

						$filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

						$league_data['url_logo'] = '/img/data/logos/leagues/' . $newName;
					}
				}

				$date = date('Y-m-d H:i:s');

				$league_data['name'] = $form->getValue('name');
				$league_data['description'] = $form->getValue('description');
				$league_data['user_id'] = $form->getValue('admin');
				$league_data['date_create'] = $date;
				$league_data['date_edit'] = $date;

				$league = new Application_Model_DbTable_League();
				$newLeague = $league->createRow($league_data);
				$newLeague->save();

				$this->redirect($this->view->url(array('controller' => 'league', 'action' => 'id', 'league_id' => $newLeague->id), 'defaultLeagueIdAll', true));
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$user = new Application_Model_DbTable_User();

		$users_data = $user->getUsersByRoleName('admin', 'ASC');

		if ($users_data) {
			foreach ($users_data as $user) {
				$form->admin->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
			}
			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы создать лигу.'));
		}
	}

	public function editAction() {
		$request = $this->getRequest();
		$league_id = $request->getParam('league_id');

		$league = new Application_Model_DbTable_League();
		$league_data = $league->fetchRow(array('id = ?' => $league_id));

		if (count($league_data) != 0) {
			$this->view->headTitle($this->view->translate('Редактировать'));
			$this->view->headTitle($league_data->name);

			$this->view->pageTitle($this->view->translate('Редактировать лигу') . '::' . $league_data->name);

			$league_id_all_url = $this->view->url(array('controller' => 'league', 'action' => 'id', 'league_id' => $league_id), 'defaultLeagueIdAll', true);
			// form
			$form = new Application_Form_League_Edit();
			$form->setAction($this->view->url(array('controller' => 'league', 'action' => 'edit', 'league_id' => $league_id), 'defaultLeagueAction', true));
			$form->cancel->setAttrib('onClick', 'location.href="' . $league_id_all_url . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {

					$new_league_data = array();

					if ($form->getValue('logo')) {
						if ($form->logo->receive()) {
							$file = $form->logo->getFileInfo();
							$ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
							$newName = Date('Y-m-d_H-i-s') . strtolower('_league_logo' . '.' . $ext);

							$filterRename = new Zend_Filter_File_Rename(array('target'
								=> $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

							$filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

							$new_league_data['url_logo'] = '/img/data/logos/leagues/' . $newName;

							if ($new_league_data['url_logo'] != $league_data['url_logo']) {
								unlink(APPLICATION_PATH . '/../public_html' . $league_data['url_logo']);
							}
						}
					}

					$date = date('Y-m-d H:i:s');
					$new_league_data['name'] = $form->getValue('name');
					$new_league_data['description'] = $form->getValue('description');
					$new_league_data['user_id'] = $form->getValue('admin');
					$new_league_data['date_edit'] = $date;

					$league_where = $league->getAdapter()->quoteInto('id = ?', $league_id);
					$league->update($new_league_data, $league_where);

					$this->redirect($league_id_all_url);
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$user = new Application_Model_DbTable_User();

			$users_data = $user->getUsersByRoleName('admin', 'ASC');

			if ($users_data) {
				foreach ($users_data as $user) {
					$form->admin->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
				}
				$this->view->form = $form;
			} else {
				$this->messages->addError($this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы редактировать лигу.'));
			}

			$form->name->setvalue($league_data->name);
			$form->description->setvalue($league_data->description);
			$form->admin->setvalue($league_data->user_id);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Лига не существует!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Лига не существует!'));

			$this->view->pageTitle($this->view->translate('Ошибка! Лига не существует!'));
		}
	}

	public function deleteAction() {
		// action body
	}

}