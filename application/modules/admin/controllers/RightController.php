<?php

class Admin_RightController extends App_Controller_LoaderController {

    public function init() {
	parent::init();
	$this->view->headTitle($this->view->translate('Правила'));
    }

    // action for view content type
    public function idAction() {
	$this->view->pageTitle($this->view->translate('Правила'));

	$request = $this->getRequest();
	$right_id = (int) $request->getParam('right_id');

	$right_data = $this->db->get('right')->getItem($right_id);

	if ($right_data) {
	    $this->view->right = $right_data;
	    $this->view->headTitle($right_data->name);
	    $this->view->pageTitle($right_data->name);
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

	$paginator = $this->db->get('right')->getAll(FALSE, "all", "ASC", "1", $pager_args);

	if (count($paginator)) {
	    $this->view->paginator = $paginator;
	} else {
	    $this->messages->addInfo("{$this->view->translate('Запрашиваемые типы контента на сайте не найдены!')}");
	}
    }

    // action for add new content type
    public function addAction() {
	$this->view->headTitle($this->view->translate('Добавить'));
	$this->view->pageTitle($this->view->translate('Добавить правило'));

	$request = $this->getRequest();
	// form
	$form = new Application_Form_Right_Add();
	$form->setAction(
		$this->view->url(
			array('module' => 'admin', 'controller' => 'right', 'action' => 'add'), 'default', true
		)
	);
	$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'right', 'action' => 'all'), 'right_all', true)}\"");

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {
		$date = date('Y-m-d H:i:s');
		$right_data = array(
		    'name' => strtolower($form->getValue('name')),
		    'description' => $form->getValue('description'),
		    'date_create' => $date,
		    'date_edit' => $date,
		);

		$new_right = $this->db->get('right')->createRow($right_data);
		$new_right->save();

		$this->redirect(
			$this->view->url(
				array('module' => 'admin', 'controller' => 'right', 'action' => 'id',
			    'right_id' => $new_right->id), 'right_id', true
			)
		);
	    } else {
		$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
	    }
	}

	$this->view->form = $form;
    }

    // action for edit content type
    public function editAction() {
	$request = $this->getRequest();
	$right_id = (int) $request->getParam('right_id');

	$this->view->headTitle($this->view->translate('Редактировать'));

	$right_data = $this->db->get('right')->getItem($right_id);

	if ($right_data) {
	    // form
	    $form = new Application_Form_Right_Edit();
	    $form->setAction($this->view->url(
			    array('module' => 'admin', 'controller' => 'right', 'action' => 'edit',
			'right_id' => $right_id), 'right_action', true
	    ));
	    $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'right', 'action' => 'id', 'right_id' => $right_id), 'right_id', true)}\"");

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $new_right_data = array(
			'name' => strtolower($form->getValue('name')),
			'description' => $form->getValue('description'),
			'date_edit' => date('Y-m-d H:i:s')
		    );

		    $right_where = $this->db->get('right')->getAdapter()->quoteInto('id = ?', $right_id);
		    $this->db->get('right')->update($new_right_data, $right_where);

		    $this->redirect($this->view->url(
				    array('module' => 'admin', 'controller' => 'right', 'action' => 'id',
				'right_id' => $right_id), 'right_id', true
		    ));
		} else {
		    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }
	    $this->view->headTitle($right_data->name);
	    $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$right_data->name}");

	    $form->name->setvalue($right_data->name);
	    $form->description->setvalue($right_data->description);

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
	$right_id = (int) $request->getParam('right_id');

	$right_data = $this->db->get('right')->getItem($right_id);

	if ($right_data) {
	    $this->view->headTitle($right_data->name);
	    $this->view->pageTitle("{$this->view->translate('Удалить Правила')} :: {$right_data->name}");

	    $this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить Правила')} <strong>\"{$right_data->name}\"</strong> ?");

	    $form = new Application_Form_Right_Delete();
	    $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'right', 'action' => 'delete', 'right_id' => $right_id), 'right_action', true));
	    $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'right', 'action' => 'id', 'right_id' => $right_id), 'right_id', true) . '"');

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $right_where = $this->db->get('right')->getAdapter()->quoteInto('id = ?', $right_id);
		    $this->db->get('right')->delete($right_where);

		    $this->messages->clearMessages();
		    $this->messages->addSuccess("{$this->view->translate("Правило <strong>\"{$right_data->name}\"</strong> успешно удалено")}");

		    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'right', 'action' => 'all', 'page' => 1), 'right_all', true));
		} else {
		    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $this->view->form = $form;
	    $this->view->right = $right_data;
	} else {
	    $this->messages->addError($this->view->translate('Запрашиваемое правило не найдено!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Правило не найдено!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Правило не найдено!')}");
	}
    }

}
