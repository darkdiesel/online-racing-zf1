<?php

class Admin_PrivilegeController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Привилегии ресурсов'));
	}

	// action for view privilege
	public function idAction() {
		$request = $this->getRequest();
		$privilege_id = (int) $request->getParam('privilege_id');

		$privilege_data = $this->db->get('privilege')->getItem($privilege_id);

		if ($privilege_data) {
			$this->view->privilege = $privilege_data;
			$this->view->headTitle($privilege_data->name);
			$this->view->pageTitle($privilege_data->name);
			return;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая привиллегия не найдена!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Привилегия не найдена!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	// action for view all privilege
	public function allAction() {
		$this->view->headTitle($this->view->translate('Все'));
		$this->view->pageTitle($this->view->translate('Привилегии ресурсов'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $this->db->get('privilege')->getAll(FALSE, "all", "ASC", "1", $pager_args);

		if (count($paginator)) {
			$this->view->privileges_data = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->translate('Запрашиваемые типы контента на сайте не найдены!')}");
		}
	}

	// action for add new content type
	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить привилегию'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_Privilege_Add();
		$form->setAction(
				$this->view->url(
						array('module' => 'admin', 'controller' => 'privilege', 'action' => 'add'), 'default', true
				)
		);
		$privilege_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'all'), 'adminPrivilegeAll', true);
		$form->cancel->setAttrib('onClick', "location.href=\"{$privilege_all_url}\"");

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$privilege_data = $this->db->get('privilege')->getItem(
						array(
							'name' => strtolower($form->getValue('name')),
							'resource_id' => $form->getValue('resource')
						)
				);

				if ($privilege_data) {
					$this->messages->addError($this->view->translate('Данная привилегия уже присутствует в базе данных!'));
					$add_privilege = FALSE;
				} else {
					$add_privilege = TRUE;
				}

				if ($add_privilege) {
					$date = date('Y-m-d H:i:s');
					$new_privilege_data = array(
						'name' => strtolower($form->getValue('name')),
						'resource_id' => $form->getValue('resource'),
						'description' => $form->getValue('description'),
						'date_create' => $date,
						'date_edit' => $date,
					);

					$new_privilege = $this->db->get('privilege')->createRow($new_privilege_data);
					$new_privilege->save();

					$this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id', 'privilege_id' => $new_privilege->id), 'adminPrivilegeId', true));
				}
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$this->view->form = $form;
	}

	// action for edit content type
	public function editAction() {
		$request = $this->getRequest();
		$privilege_id = (int) $request->getParam('privilege_id');

		$this->view->headTitle($this->view->translate('Редактировать'));

		$privilege_data = $this->db->get('privilege')->getItem($privilege_id);

		if ($privilege_data) {
			// form
			$form = new Application_Form_Right_Edit();
			$form->setAction($this->view->url(
							array('module' => 'admin', 'controller' => 'privilege', 'action' => 'edit',
						'privilege_id' => $privilege_id), 'privilege_action', true
			));
			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id', 'privilege_id' => $privilege_id), 'privilege_id', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$new_privilege_data = array(
						'name' => strtolower($form->getValue('name')),
						'description' => $form->getValue('description'),
						'date_edit' => date('Y-m-d H:i:s')
					);

					$privilege_where = $this->db->get('privilege')->getAdapter()->quoteInto('id = ?', $privilege_id);
					$this->db->get('privilege')->update($new_privilege_data, $privilege_where);

					$this->redirect($this->view->url(
									array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id',
								'privilege_id' => $privilege_id), 'privilege_id', true
					));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}
			$this->view->headTitle($privilege_data->name);
			$this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$privilege_data->name}");

			$form->name->setvalue($privilege_data->name);
			$form->description->setvalue($privilege_data->description);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемое правило не найдено!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Правило не найдено!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	// action for delete content type
	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));

		$request = $this->getRequest();
		$privilege_id = (int) $request->getParam('privilege_id');

		$privilege_data = $this->db->get('privilege')->getItem($privilege_id);

		if ($privilege_data) {
			$this->view->headTitle($privilege_data->name);
			$this->view->pageTitle("{$this->view->translate('Удалить Правила')} :: {$privilege_data->name}");

			$this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить Правила')} <strong>\"{$privilege_data->name}\"</strong> ?");

			$form = new Application_Form_Right_Delete();
			$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'delete', 'privilege_id' => $privilege_id), 'privilege_action', true));
			$form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id', 'privilege_id' => $privilege_id), 'privilege_id', true) . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$privilege_where = $this->db->get('privilege')->getAdapter()->quoteInto('id = ?', $privilege_id);
					$this->db->get('privilege')->delete($privilege_where);

					$this->messages->clearMessages();
					$this->messages->addSuccess("{$this->view->translate("Правило <strong>\"{$privilege_data->name}\"</strong> успешно удалено")}");

					$this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'all', 'page' => 1), 'privilege_all', true));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->form = $form;
			$this->view->privilege = $privilege_data;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемое правило не найдено!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Правило не найдено!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Правило не найдено!')}");
		}
	}

}
