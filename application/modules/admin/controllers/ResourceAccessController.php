<?php

class Admin_ResourceAccessController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->pageTitle($this->view->translate('Доступ к ресурсам'));
	}

	// action for view content type
	public function idAction() {
		$request = $this->getRequest();
		$resource_access_id = (int) $request->getParam('resource_access_id');

		$resource_access_data = $this->db->get('resource_access')->getItem($resource_access_id);

		if ($resource_access_data) {
			$this->view->resource_access = $resource_access_data;
			$this->view->headTitle($resource_access_data->id);
			$this->view->pageTitle($this->view->translate('Доступ к ресурсам') . " : " . $resource_access_data->id);
			return;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемый доступ к ресурсам не найден!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Доступ к ресурсам не найден!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	// action for view all content types
	public function allAction() {
		$this->view->headTitle($this->view->translate('Все'));
		$this->view->pageTitle($this->view->translate('Доступы к ресурсам'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $this->db->get('resource_access')->getAll(FALSE, "all", "ASC", "1", $pager_args);

		if (count($paginator)) {
			$this->view->resource_access_data = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->translate('Запрашиваемые доступы к ресурсам на сайте не найдены!')}");
		}
	}

	// action for add new content type
	public function addAction() {
		$this->view->headTitle($this->view->translate('Настроить'));
		$this->view->pageTitle($this->view->translate('Настроить доступ к ресурсу'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_ResourceAccess_Add();
		$form->setAction(
				$this->view->url(
						array('module' => 'admin', 'controller' => 'resource-access', 'action' => 'add'), 'default', true
				)
		);
		$resource_access_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'resource-access', 'action' => 'all'), 'adminResourceAccessAll', true);
		$form->cancel->setAttrib('onClick', "location.href=\"{$resource_access_all_url}\"");

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$date = date('Y-m-d H:i:s');
				$new_resource_access_data = array(
					'role_id' => $form->getValue('role'),
					'resource_id' => $form->getValue('resource'),
					'privilege_id' => $form->getValue('privilege'),
					'allow' => $form->getValue('allow'),
					'date_create' => $date,
					'date_edit' => $date,
				);

				$check_resource_access_data = $this->db->get('resource_access')->getItem(
						array(
							'role_id' => $new_resource_access_data['role_id'],
							'resource_id' => array(
								'value' => $new_resource_access_data['resource_id'],
								'condition' => 'AND'
							),
							'privilege_id' => array(
								'value' => $new_resource_access_data['privilege_id'],
								'condition' => 'AND'
							),
				));

				if ($check_resource_access_data) {
					$this->messages->addError(
							$this->view->translate('Доступ для выбранного ресурса, привилегии и роли уже существуют. Отредактируйте уже созданный доступ либо поменяйте параметры для добавления нового.')
					);
				} else {
					$new_resource_access = $this->db->get('resource_access')->createRow($new_resource_access_data);
					$new_resource_access->save();

					$this->redirect(
							$this->view->url(
									array('module' => 'admin', 'controller' => 'resource-access', 'action' => 'id', 'resource_access_id' => $new_resource_access->id), 'adminResourceAccessId', true
							)
					);
				}
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$this->view->form = $form;
	}

	// action for edit resource access
	public function editAction() {
		$request = $this->getRequest();
		$resource_access_id = (int) $request->getParam('resource_access_id');

		$this->view->headTitle($this->view->translate('Редактировать'));

		$resource_access_data = $this->db->get('resource_access')->getItem($resource_access_id);

		if ($resource_access_data) {
			// form
			$form = new Application_Form_ResourceAccess_Edit();
			$form->setAction(
					$this->view->url(
							array('module' => 'admin', 'controller' => 'resource-access', 'action' => 'edit', 'resource_access_id' => $resource_access_id), 'adminResourceAccessAction', true
					)
			);
			$resource_access_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'resource-access', 'action' => 'id', 'resource_access_id' => $resource_access_id), 'adminResourceAccessId', true);
			$form->cancel->setAttrib('onClick', "location.href=\"{$resource_access_id_url}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$date = date('Y-m-d H:i:s');
					$new_resource_access_data = array(
						'role_id' => $form->getValue('role'),
						'resource_id' => $form->getValue('resource'),
						'privilege_id' => $form->getValue('privilege'),
						'allow' => $form->getValue('allow'),
						'date_edit' => $date,
					);

					$check_resource_access_data = $this->db->get('resource_access')->getItem(
							array(
								'role_id' => $new_resource_access_data['role_id'],
								'resource_id' => array(
									'value' => $new_resource_access_data['resource_id'],
									'condition' => 'AND'
								),
								'privilege_id' => array(
									'value' => $new_resource_access_data['privilege_id'],
									'condition' => 'AND'
								),
					));

					$update_resource_access = TRUE;
					if ($check_resource_access_data) {
						if ($check_resource_access_data->id != $resource_access_data->id) {
							$update_resource_access = FALSE;
							$this->messages->addError(
									$this->view->translate('Доступ для выбранного ресурса, привилегии и роли уже существуют. Отредактируйте уже созданный доступ либо поменяйте параметры для добавления нового.')
							);
						}
					}

					if ($update_resource_access) {
						$resource_access_where = $this->db->get('resource_access')->getAdapter()->quoteInto('id = ?', $resource_access_id);
						$this->db->get('resource_access')->update($new_resource_access_data, $resource_access_where);

						$this->redirect($resource_access_id_url);
					}
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}
			$this->view->headTitle($resource_access_data->id);
			$this->view->pageTitle($this->view->translate('Редактировать права доступа') . ' : ' . $resource_access_data->id);

			$form->role->setvalue($resource_access_data->role_id);
			$form->resource->setvalue($resource_access_data->resource_id);
			$form->privilege->setvalue($resource_access_data->privilege_id);
			$form->allow->setvalue($resource_access_data->allow);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемый доступ к ресурсам не найден!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Доступ к ресурсам не найден!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	// action for delete content type
	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));

		$request = $this->getRequest();
		$content_type_id = (int) $request->getParam('content_type_id');

		$content_type = new Application_Model_DbTable_ContentType();
		$content_type_data = $content_type->getItem($content_type_id);

		if ($content_type_data) {
			$this->view->headTitle($content_type_data->name);
			$this->view->pageTitle("{$this->view->translate('Удалить Правила')} :: {$content_type_data->name}");

			$this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить Правила')} <strong>\"{$content_type_data->name}\"</strong> ?");

			$form = new Application_Form_PostType_Delete();
			$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'delete', 'content_type_id' => $content_type_id), 'content_type_action', true));
			$form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id', 'content_type_id' => $content_type_id), 'content_type_id', true) . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$content_type_where = $content_type->getAdapter()->quoteInto('id = ?', $content_type_id);
					$content_type->delete($content_type_where);

					$this->view->showMessages()->clearMessages();
					$this->messages->addSuccess("{$this->view->translate("Правила <strong>\"{$content_type_data->name}\"</strong> успешно удален")}");

					$this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'all', 'page' => 1), 'content_type_all', true));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->form = $form;
			$this->view->content_type = $content_type_data;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемое правило не найдено!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Правило не найдено!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Правило не найдено!')}");
		}
	}

}
