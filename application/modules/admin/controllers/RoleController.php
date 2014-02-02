<?php

class Admin_RoleController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Роли Пользователей'));
	}

	public function idAction() {
		$request = $this->getRequest();
		$role_id = (int) $request->getParam('role_id');

		$role_data = $this->db->get('role')->getItem($role_id);

		if ($role_data) {
			$this->view->role = $role_data;
			$this->view->headTitle($role_data->name);
			$this->view->pageTitle($role_data->name);
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая роль пользователя не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Роль пользователя не найдена!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Роль пользователя не найдена!')}");
		}
	}

	// action for view all roles
	public function allAction() {
		$this->view->headTitle($this->view->translate('Все'));
		$this->view->pageTitle($this->view->translate('Роли пользователей'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $this->db->get("role")->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

		if ($paginator) {
			$this->view->roles_data = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->translate('Запрашиваемые роли пользователей на сайте не найдены!')}");
		}
	}

	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить роль пользователя'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_Role_Add();
		$form->setAction(
				$this->view->url(
						array('module' => 'admin', 'controller' => 'role', 'action' => 'add'), 'default', true
				)
		);
		$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin','controller' => 'role', 'action' => 'all'), 'adminRoleAll', true)}\"");

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$date = date('Y-m-d H:i:s');
				$new_role_data = array(
					'name' => strtolower($form->getValue('name')),
					'parent_role_id' => strtolower($form->getValue('parent_role')),
					'description' => $form->getValue('description'),
					'date_create' => $date,
					'date_edit' => $date,
				);

				$new_role = $this->db->get('role')->createRow($new_role_data);
				$new_role->save();

				$this->redirect(
						$this->view->url(
								array('module' => 'admin', 'controller' => 'role', 'action' => 'id',
							'role_id' => $new_role->id), 'role_id', true
						)
				);
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$this->view->form = $form;
	}

	// action for editing role
	public function editAction() {
		$request = $this->getRequest();
		$role_id = (int) $request->getParam('role_id');

		$this->view->headTitle($this->view->translate('Редактировать'));

		$role_data = $this->db->get('role')->getItem($role_id);

		if ($role_data) {
			// form
			$form = new Application_Form_Role_Edit();
			$form->setAction($this->view->url(
							array('module' => 'admin', 'controller' => 'role', 'action' => 'edit',
						'role_id' => $role_id), 'role_action', true
			));
			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'role_id' => $role_id), 'role_id', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$new_role_data = array(
						'name' => strtolower($form->getValue('name')),
						'parent_role_id' => strtolower($form->getValue('parent_role')),
						'description' => $form->getValue('description'),
						'date_edit' => date('Y-m-d H:i:s')
					);

					$role_where = $this->db->get('role')->getAdapter()->quoteInto('id = ?', $role_id);
					$this->db->get('role')->update($new_role_data, $role_where);

					$this->redirect($this->view->url(
									array('module' => 'admin', 'controller' => 'role', 'action' => 'id',
								'role_id' => $role_id), 'role_id', true
					));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}
			$this->view->headTitle($role_data->name);
			$this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$role_data->name}");

			$form->name->setvalue($role_data->name);
			$form->parent_role->setvalue($role_data->parent_role_id);
			$form->description->setvalue($role_data->description);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая роль пользователя не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Роль пользователя не найдена!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Роль пользователя не найдена!')}");
		}
	}

	// action for delete role
	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));

		$request = $this->getRequest();
		$role_id = (int) $request->getParam('role_id');

		$role_data = $this->db->get('role')->getItem($role_id);

		if ($role_data) {
			$this->view->headTitle($role_data->name);
			$this->view->pageTitle("{$this->view->translate('Удалить ресурс')} :: {$role_data->name}");

			$this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить ресурс')} <strong>\"{$role_data->name}\"</strong> ?");

			$form = new Application_Form_Role_Delete();
			$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'delete', 'role_id' => $role_id), 'role_action', true));
			$form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'id', 'role_id' => $role_id), 'role_id', true) . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$role_where = $this->db->get('role')->getAdapter()->quoteInto('id = ?', $role_id);
					$this->db->get('role')->delete($role_where);

					$this->messages->clearMessages();
					$this->messages->addSuccess("{$this->view->translate("Роль пользователя <strong>\"{$role_data->name}\"</strong> успешно удалена")}");

					$this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'role', 'action' => 'all', 'page' => 1), 'adminRoleAll', true));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->form = $form;
			$this->view->role = $role_data;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая роль пользователя не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Роль пользователя не существует!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Роль пользователя не существует!')}");
		}
	}

}
