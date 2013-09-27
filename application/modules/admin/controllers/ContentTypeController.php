<?php

class Admin_ContentTypeController extends App_Controller_LoaderController
{

    public function init()
    {
	parent::init();
	$this->view->headTitle($this->view->translate('Тип контента'));
    }

    // action for view content type
    public function idAction()
    {
	$this->view->pageTitle($this->view->translate('Тип контента'));

	$request = $this->getRequest();
	$content_type_id = (int) $request->getParam('content_type_id');

	$content_type = new Application_Model_DbTable_ContentType();
	$content_type_data = $content_type->getItem($content_type_id);

	if ($content_type_data) {
	    $this->view->content_type = $content_type_data;
	    $this->view->headTitle($content_type_data->name);
	    $this->view->pageTitle($content_type_data->name);
	    return;
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемый тип контента не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не найден!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип контента не найден!')}");
	}
    }

    // action for view all content types
    public function allAction()
    {
	$this->view->headTitle($this->view->translate('Типы контента'));
	$this->view->pageTitle($this->view->translate('Типы контента'));

	$content_type = new Application_Model_DbTable_ContentType();

	// pager settings
	$pager_args = array(
	    "page_count_items" => 10,
	    "page_range" => 5,
	    "page" => $this->getRequest()->getParam('page')
	);

	$paginator = $content_type->getAll("all", "ASC", "1", $pager_args);

	if (count($paginator)) {
	    $this->view->paginator = $paginator;
	} else {
	    $this->messageManager->addInfo("{$this->view->translate('Запрашиваемые типы контента на сайте не найдены!')}");
	}
    }

    // action for add new content type
    public function addAction()
    {
	$this->view->headTitle($this->view->translate('Добавить'));
	$this->view->pageTitle($this->view->translate('Добавить тип контента'));

	$request = $this->getRequest();
	// form
	$form = new Application_Form_ContentType_Add();
	$form->setAction(
		$this->view->url(
			array('module' => 'admin', 'controller' => 'content-type', 'action' => 'add'), 'default', true
		)
	);

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {
		$date = date('Y-m-d H:i:s');
		$content_type_data = array(
		    'name' => strtolower($form->getValue('name')),
		    'description' => $form->getValue('description'),
		    'date_create' => $date,
		    'date_edit' => $date,
		);

		$content_type = new Application_Model_DbTable_ContentType();
		$new_content_type = $content_type->createRow($content_type_data);
		$new_content_type->save();

		$this->redirect(
			$this->view->url(
				array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id',
			    'content_type_id' => $new_content_type->id), 'content_type_id', true
			)
		);
	    } else {
		$this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
	    }
	}

	$this->view->form = $form;
    }

    // action for edit content type
    public function editAction()
    {
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
		    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }
	    $this->view->headTitle($content_type_data->name);
	    $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$content_type_data->name}");

	    $form->name->setvalue($content_type_data->name);
	    $form->description->setvalue($content_type_data->description);

	    $this->view->form = $form;
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемый тип контента не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не найден!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип контента не найден!')}");
	}
    }

    // action for delete content type
    public function deleteAction()
    {
	$this->view->headTitle($this->view->translate('Удалить'));

	$request = $this->getRequest();
	$content_type_id = (int) $request->getParam('content_type_id');

	$content_type = new Application_Model_DbTable_ContentType();
	$content_type_data = $content_type->getItem($content_type_id);

	if ($content_type_data) {
	    $this->view->headTitle($content_type_data->name);
	    $this->view->pageTitle("{$this->view->translate('Удалить тип контента')} :: {$content_type_data->name}");

	    $this->messageManager->addWarning("{$this->view->translate('Вы действительно хотите удалить тип контента')} <strong>\"{$content_type_data->name}\"</strong> ?");

	    $form = new Application_Form_ArticleType_Delete();
	    $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'delete', 'content_type_id' => $content_type_id), 'content_type_action', true));
	    $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'id', 'content_type_id' => $content_type_id), 'content_type_id', true) . '"');

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $content_type_where = $content_type->getAdapter()->quoteInto('id = ?', $content_type_id);
		    $content_type->delete($content_type_where);

		    $this->view->showMessages()->clearMessages();
		    $this->messageManager->addSuccess("{$this->view->translate("Тип контента <strong>\"{$content_type_data->name}\"</strong> успешно удален")}");

		    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'content-type', 'action' => 'all', 'page' => 1), 'content_type_all', true));
		} else {
		    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $this->view->form = $form;
	    $this->view->content_type = $content_type_data;
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемый тип контента не найден!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не найден!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип контента не найден!')}");
	}
    }

}