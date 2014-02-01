<?php

class Admin_AccessController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Привилегии ресурсов'));
	}

	// action for view content type
	public function idAction() {
		$request = $this->getRequest();
		$privilege_id = (int) $request->getParam('privilege_id');

		$privilege_data = $this->db->get('peivilege')->getItem($privilege_id);

		if ($privilege_data) {
			$this->view->privilege = $privilege_data;
			$this->view->headTitle($privilege_data->name);
			$this->view->pageTitle($privilege_data->name);
			return;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемое правило не найдено!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Правило не найдено!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Правило не найдено!')}");
		}
	}

	// action for view all content types
	public function allAction() {
		$this->view->headTitle($this->view->translate('Правила'));
		$this->view->pageTitle($this->view->translate('Правила'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $this->db->get('access')->getAll(FALSE, "all", "ASC", "1", $pager_args);

		if (count($paginator)) {
			$this->view->access_data = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->translate('Запрашиваемые типы контента на сайте не найдены!')}");
		}
	}

	// action for add new content type
	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить привилегии'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_Privilege_Add();
		$form->setAction(
				$this->view->url(
						array('module' => 'admin', 'controller' => 'privilege', 'action' => 'add'), 'default', true
				)
		);

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				$date = date('Y-m-d H:i:s');
				$privilege_data = array(
					'role_id' => $form->getValue('role'),
					'resource_id' => $form->getValue('resource'),
					'right_id' => $form->getValue('right'),
				);

				$exist_privilege = $this->db->get('privilege')->getItem(array('role_id' => $privilege_data['role_id'], 'resource_id' => array('value' => $privilege_data['resource_id'], 'condition' => 'AND')));

				if ($exist_privilege) {
					$this->messages->addError($this->view->translate('Привилегии для выбранного ресурса и роли уже существуют. Отредактируйте уже созданную либо поменяйте параметры для добавления новой привилегии.'));
				} else {
					$new_privilege = $this->db->get('privilege')->createRow($privilege_data);
					$new_privilege->save();

					$this->redirect(
							$this->view->url(
									array('module' => 'admin', 'controller' => 'privilege', 'action' => 'all'), 'adminPrivilegeAll', true
							)
					);
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
		$content_type_id = (int) $request->getParam('content_type_id');

		$this->view->headTitle($this->view->translate('Редактировать'));

		$content_type = new Application_Model_DbTable_ContentType();
		$content_type_data = $content_type->getItem($content_type_id);

		if ($content_type_data) {
			// form
			$form = new Application_Form_ContentType_Edit();
			$form->setAction($this->view->url(
							array('module' => 'admin', 'controller' => 'content-type', 'action' => 'edit',
						'content_type_id' => $content_type_id), 'content_type_action', true
			));
			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'all'), 'default', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$new_content_type_data = array(
						'name' => strtolower($form->getValue('name')),
						'description' => $form->getValue('description'),
						'date_edit' => date('Y-m-d H:i:s')
					);

					$content_type_where = $content_type->getAdapter()->quoteInto('id = ?', $content_type_id);
					$content_type->update($new_content_type_data, $content_type_where);

					$this->redirect($this->view->url(
									array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id',
								'content_type_id' => $content_type_id), 'content_type_id', true
					));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}
			$this->view->headTitle($content_type_data->name);
			$this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$content_type_data->name}");

			$form->name->setvalue($content_type_data->name);
			$form->description->setvalue($content_type_data->description);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемое правило не найдено!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Правило не найдено!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Правило не найдено!')}");
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
