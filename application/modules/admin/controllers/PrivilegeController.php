<?php

class Admin_PrivilegeController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->t('Привилегии ресурсов'));
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
			$this->messages->addError($this->view->t('Запрашиваемая привиллегия не найдена!'));
			$this->view->headTitle($this->view->t('Ошибка!'));
			$this->view->headTitle($this->view->t('Привилегия не найдена!'));
			$this->view->pageTitle($this->view->t('Ошибка!'));
		}
	}

	// action for view all privilege
	public function allAction() {
		$this->view->headTitle($this->view->t('Все'));
		$this->view->pageTitle($this->view->t('Привилегии ресурсов'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $this->db->get('privilege')->getAll(FALSE, "all", "ASC", "1", $pager_args);

		if ($paginator) {
			$this->view->privileges_data = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->t('Запрашиваемые типы контента на сайте не найдены!')}");
		}
	}

	// action for add new content type
	public function addAction() {
		$this->view->headTitle($this->view->t('Добавить'));
		$this->view->pageTitle($this->view->t('Добавить привилегию'));

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
				$check_privilege_data = $this->db->get('privilege')->getItem(
						array(
							'name' => strtolower($form->getValue('name')),
							'resource_id' => array(
								'value' => $form->getValue('resource'),
								'condition' => 'AND'
							)
						)
				);

				if ($check_privilege_data) {
					$this->messages->addError($this->view->t('Для данного ресурса эта привиления уже присутствует в базе данных!'));
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
				$this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		$this->view->form = $form;
	}

	// action for edit content type
	public function editAction() {
		$request = $this->getRequest();
		$privilege_id = (int) $request->getParam('privilege_id');

		$this->view->headTitle($this->view->t('Редактировать привилегию'));

		$privilege_data = $this->db->get('privilege')->getItem($privilege_id);

		if ($privilege_data) {
			// form
			$form = new Application_Form_Privilege_Edit();
			$form->setAction($this->view->url(
							array('module' => 'admin', 'controller' => 'privilege', 'action' => 'edit',
						'privilege_id' => $privilege_id), 'adminPrivilegeAction', true
			));
			$privilege_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id', 'privilege_id' => $privilege_id), 'adminPrivilegeId', true);
			$form->cancel->setAttrib('onClick', "location.href='{$privilege_id_url}'");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$check_privilege_data = $this->db->get('privilege')->getItem(
							array(
								'name' => strtolower($form->getValue('name')),
								'resource_id' => array(
										'value' => $form->getValue('resource'),
										'condition' => 'AND'
									)
							)
					);

					$upadate_privilege = TRUE;
					if ($check_privilege_data) {
						if ($check_privilege_data->id != $privilege_id) {
							$this->messages->addError($this->view->t('Для данного ресурса эта привиления уже присутствует в базе данных!'));
							$upadate_privilege = FALSE;
						}
					}

					if ($upadate_privilege) {
						$new_privilege_data = array(
							'name' => strtolower($form->getValue('name')),
							'resource_id' => strtolower($form->getValue('resource')),
							'description' => $form->getValue('description'),
							'date_edit' => date('Y-m-d H:i:s')
						);

						$privilege_where = $this->db->get('privilege')->getAdapter()->quoteInto('id = ?', $privilege_id);
						$this->db->get('privilege')->update($new_privilege_data, $privilege_where);

						$this->redirect($privilege_id_url);
					}
				} else {
					$this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}
			$this->view->headTitle($privilege_data->name);
			$this->view->pageTitle($this->view->t('Редактировать привилегию'));

			$form->name->setvalue($privilege_data->name);
			$form->resource->setvalue($privilege_data->resource_id);
			$form->description->setvalue($privilege_data->description);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->t('Запрашиваемое правило не найдено!'));
			$this->view->headTitle($this->view->t('Ошибка!'));
			$this->view->headTitle($this->view->t('Правило не найдено!'));
			$this->view->pageTitle($this->view->t('Ошибка!'));
		}
	}

	// action for delete content type
	public function deleteAction() {
		$this->view->headTitle($this->view->t('Удалить привилегию'));

		$request = $this->getRequest();
		$privilege_id = (int) $request->getParam('privilege_id');

		$privilege_data = $this->db->get('privilege')->getItem($privilege_id);

		if ($privilege_data) {
			$this->view->headTitle($privilege_data->name);
			$this->view->pageTitle($this->view->t('Удалить привилегию'));

			$this->messages->addWarning("{$this->view->t('Вы действительно хотите удалить привилегию')} <strong>\"{$privilege_data->name}\"</strong> ?");

			$form = new Application_Form_Privilege_Delete();
			$form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'delete', 'privilege_id' => $privilege_id), 'adminPrivilegeAction', true));
			$form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'id', 'privilege_id' => $privilege_id), 'adminPrivilegeId', true) . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$privilege_where = $this->db->get('privilege')->getAdapter()->quoteInto('id = ?', $privilege_id);
					$this->db->get('privilege')->delete($privilege_where);

					$this->messages->clearMessages();
					$this->messages->addSuccess("{$this->view->t("Привилегия <strong>\"{$privilege_data->name}\"</strong> успешно удалена")}");

					$this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'privilege', 'action' => 'all', 'page' => 1), 'adminPrivilegeAll', true));
				} else {
					$this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->form = $form;
			$this->view->privilege = $privilege_data;
		} else {
			$this->messages->addError($this->view->t('Запрашиваемая привилегия не найдена!'));
			$this->view->headTitle($this->view->t('Ошибка!'));
			$this->view->headTitle($this->view->t('Привилегия не найдена!'));
			$this->view->pageTitle($this->view->t('Ошибка!'));
		}
	}

}
