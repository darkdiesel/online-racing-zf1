<?php

class Admin_EventController extends App_Controller_LoaderController
{

    public function init()
    {
	parent::init();
	$this->view->headTitle($this->view->t('Событие'));
    }

    // action for view content type
    public function idAction()
    {
	$request = $this->getRequest();
	$event_id = (int) $request->getParam('event_id');

	$event = new Application_Model_DbTable_Event();
	$event_data = $event->getItem($event_id);

	if ($event_data) {
	    $this->view->event = $event_data;
	    $this->view->headTitle($event_data->name);
	    $this->view->pageTitle($event_data->name);
	    return;
	} else {
	    $this->messages->addError($this->view->t('Запрашиваемое событие не найдено!'));
	    $this->view->headTitle("{$this->view->t('Ошибка!')} :: {$this->view->t('Событие не найдено!')}");
	    $this->view->pageTitle("{$this->view->t('Ошибка!')} {$this->view->t('Событие не найдено!')}");
	}
    }

    // action for view all content types
    public function allAction()
    {
	$this->view->headTitle($this->view->t('События сайта'));
	$this->view->pageTitle($this->view->t('События сайта'));

	$event = new Application_Model_DbTable_Event();

	// pager settings
	$pager_args = array(
	    "page_count_items" => 10,
	    "page_range" => 5,
	    "page" => $this->getRequest()->getParam('page')
	);

	$paginator = $event->getAll("all", array('date_event', 'DESC'), TRUE, $pager_args);

	if (count($paginator)) {
	    $this->view->paginator = $paginator;
	} else {
	    $this->messages->addInfo("{$this->view->t('События на сайте не найдены!')}");
	}
    }

    // action for add new content type
    public function addAction()
    {
	$this->view->headTitle($this->view->t('Добавить'));
	$this->view->pageTitle($this->view->t('Добавить событие'));

	// css and js for date time picker script
	$this->view->headLink()->appendStylesheet($this->view->baseUrl("/library/jquery/css/jquery-ui-timepicker-addon.min.css"));
	$this->view->headScript()->appendFile($this->view->baseUrl("/library/jquery/js/jquery-ui-timepicker-addon.min.js"));

	$request = $this->getRequest();
	// form
	$form = new Application_Form_Event_Add();
	$form->setAction(
		$this->view->url(
			array('module' => 'admin', 'controller' => 'event', 'action' => 'add'), 'default', true
		)
	);
	$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'event', 'action' => 'all'), 'event_all', true)}\"");

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {
		$date = date('Y-m-d H:i:s');
		$event_data = array(
		    'name' => $form->getValue('name'),
		    'description' => $form->getValue('description'),
		    'date_event' => $form->getValue('date_event'),
		    'date_create' => $date,
		    'date_edit' => $date,
		    'url_event' => $form->getValue('url_event'),
		);

		$event = new Application_Model_DbTable_Event();
		$newEvent = $event->createRow($event_data);
		$newEvent->save();

		$this->redirect(
			$this->view->url(
				array('module' => 'admin', 'controller' => 'event', 'action' => 'id',
			    'event_id' => $newEvent->id), 'event_id', true
			)
		);
	    } else {
		$this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
	    }
	}

	$this->view->form = $form;
    }

    // action for edit content type
    public function editAction()
    {
	$request = $this->getRequest();
	$event_id = (int) $request->getParam('event_id');
	
	$this->view->headTitle($this->view->t('Редактировать'));

	// css and js for date time picker script
	$this->view->headLink()->appendStylesheet($this->view->baseUrl("/library/jquery/css/jquery-ui-timepicker-addon.min.css"));
	$this->view->headScript()->appendFile($this->view->baseUrl("/library/jquery/js/jquery-ui-timepicker-addon.min.js"));

	$event = new Application_Model_DbTable_Event();
	$event_data = $event->getItem($event_id);

	if ($event_data) {
	    // form
	    $form = new Application_Form_Event_Edit();
	    $form->setAction($this->view->url(
			    array('module' => 'admin', 'controller' => 'event', 'action' => 'edit',
			'event_id' => $event_id), 'event_action', true
	    ));
	    $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'event', 'action' => 'id', 'event_id' => $event_id), 'event_id', true)}\"");

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $event_data = array(
			'name' => $form->getValue('name'),
			'description' => $form->getValue('description'),
			'date_event' => $form->getValue('date_event'),
			'date_edit' => date('Y-m-d H:i:s'),
			'url_event' => $form->getValue('url_event')
		    );

		    $event_where = $event->getAdapter()->quoteInto('id = ?', $event_id);
		    $event->update($event_data, $event_where);

		    $this->redirect($this->view->url(
				    array('module' => 'admin', 'controller' => 'event', 'action' => 'id',
				'event_id' => $event_id), 'event_id', true
		    ));
		} else {
		    $this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $this->view->headTitle($event_data->name);
	    $this->view->pageTitle("{$this->view->t('Редактировать')} :: {$event_data->name}");

	    $form->name->setvalue($event_data->name);
	    $form->date_event->setvalue($event_data->date_event);
	    $form->url_event->setvalue($event_data->url_event);
	    $form->description->setvalue($event_data->description);

	    $this->view->form = $form;
	} else {
	    $this->messages->addError($this->view->t('Запрашиваемое событие не найдено!'));
	    $this->view->headTitle("{$this->view->t('Ошибка!')} :: {$this->view->t('Событие не найдено!')}");
	    $this->view->pageTitle("{$this->view->t('Ошибка!')} {$this->view->t('Событие не найдено!')}");
	}
    }

    // action for delete content type
    public function deleteAction()
    {
	$this->view->headTitle($this->view->t('Удалить'));

	$request = $this->getRequest();
	$event_id = (int) $request->getParam('event_id');

	$event = new Application_Model_DbTable_Event();
	$event_data = $event->getItem($event_id);

	if (count($event_data) != 0) {
	    $this->view->headTitle($event_data->name);
	    $this->view->pageTitle("{$this->view->t('Удалить событие')} :: {$event_data->name}");

	    $this->messages->addWarning("{$this->view->t('Вы действительно хотите удалить событие')} <strong>\"{$event_data->name}\"</strong> ?");

	    $form = new Application_Form_Event_Delete();
	    $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'event', 'action' => 'delete', 'event_id' => $event_id), 'event_action', true));
	    $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'event', 'action' => 'id', 'event_id' => $event_id), 'event_id', true) . '"');

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $event_where = $event->getAdapter()->quoteInto('id = ?', $event_id);
		    $event->delete($event_where);

		    $this->view->showMessages()->clearMessages();
		    $this->messages->addSuccess("{$this->view->t("Событие <strong>\"{$event_data->name}\"</strong> успешно удалено")}");

		    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'event', 'action' => 'all', 'page' => 1), 'event_all', true));
		} else {
		    $this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $this->view->form = $form;
	    $this->view->event = $event_data;
	} else {
	    $this->messages->addError($this->view->t('Запрашиваемое событие не найдено!'));
	    $this->view->headTitle("{$this->view->t('Ошибка!')} :: {$this->view->t('Событие не найдено!')}");
	    $this->view->pageTitle("{$this->view->t('Ошибка!')} {$this->view->t('Событие не найдено!')}");
	}
    }

}