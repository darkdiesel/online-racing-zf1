<?php

class Admin_UserController extends App_Controller_LoaderController
{

    public function init()
    {
	parent::init();
	$this->view->headTitle($this->view->translate('Пользователи'));
    }
    
    public function idAction()
    {
	
    }
    
    // action for view all users
    public function allAction()
    {
	$this->view->headTitle($this->view->translate('Все'));
	$this->view->pageTitle($this->view->translate('Пользователи'));

	// pager settings
	$pager_args = array(
	    "page_count_items" => 10,
	    "page_range" => 5,
	    "page" => $this->getRequest()->getParam('page')
	);

	$paginator = $this->db->get("user")->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

	if (count($paginator)) {
	    $this->view->paginator = $paginator;
	} else {
	    $this->messageManager->addInfo("{$this->view->translate('Запрашиваемые пользователи на сайте не найдены!')}");
	}
    }
    
    // action for editing role
    public function editAction()
    {
	$request = $this->getRequest();
	$user_id = (int) $request->getParam('user_id');

	$this->view->headTitle($this->view->translate('Редактировать'));

	$user_data = $this->db->get('user')->getItem($user_id);

	if ($user_data) {
	    // form
	    $form = new Application_Form_User_Admin_Edit();
	    $form->setAction($this->view->url(
			    array('module' => 'admin', 'controller' => 'user', 'action' => 'edit',
			'user_id' => $user_id), 'user_action', true
	    ));
	    $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'user', 'action' => 'all'), 'default', true)}\"");

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {
		    $new_user_role_data = array(
			'user_id' => $user_id,
			'role_id' => $form->getValue('user_role'),
		    );

		    $user_where = $this->db->get('user_role')->getAdapter()->quoteInto('user_id = ?', $user_id);
		    $updated = $this->db->get('user_role')->update($new_user_role_data, $user_where);
		    
		    if (!$updated){
			$new_user_role = $this->db->get('user_role')->createRow($new_user_role_data);
			$new_user_role->save();
		    }

		    $this->redirect($this->view->url(
				    array('module' => 'admin', 'controller' => 'user', 'action' => 'id',
				'user_id' => $user_id), 'user_id', true
		    ));
		} else {
		    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }
	    $this->view->headTitle($user_data->name);
	    $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$user_data->name}");

	    $form->user_role->setvalue($user_data->user_role_id);

	    $this->view->form = $form;
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемая роль пользователя не найдена!'));
	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Роль пользователя не найдена!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Роль пользователя не найдена!')}");
	}
    }
}