<?php

class ResourceController extends App_Controller_FirstBootController {

    public function init() {
	parent::init();
	$this->view->headTitle($this->view->translate('Тип контента'));
    }

    public function indexAction() {
	// action body
    }

    public function addAction() {
	$this->view->headTitle($this->view->translate('Добавить'));
	$this->view->pageTitle($this->view->translate('Добавить ресурс'));

	$request = $this->getRequest();
	// form
	$form = new Application_Form_Resource_Add();
	$form->setAction(
		$this->view->url(
			array('controller' => 'resource', 'action' => 'add'), 'default', true
		)
	);

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {
		$date = date('Y-m-d H:i:s');
		$new_resource_data = array(
		    'name' => strtolower($form->getValue('name')),
		    'controllers' => strtolower($form->getValue('controller')),
		    'action' => strtolower($form->getValue('action')),
		    'description' => $form->getValue('description'),
		    'date_create' => $date,
		    'date_edit' => $date,
		);

		$resource = new Application_Model_DbTable_Resource();
		$new_resource = $resource->createRow($new_resource_data);
		$new_resource->save();

		$this->redirect(
			$this->view->url(
				array('controller' => 'resource', 'action' => 'id',
			    'resource_id' => $new_resource->id), 'resourceId', true
			)
		);
	    } else {
		$this->messageManager->addError($this->view->translate('Исправте следующие ошибки для добавления типа контента!'));
	    }
	}

	$this->view->form = $form;
    }

}