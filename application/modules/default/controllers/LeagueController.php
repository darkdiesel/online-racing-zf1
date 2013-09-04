<?php

class LeagueController extends App_Controller_FirstBootController
{

    public function init()
    {
	parent::init();
	$this->view->headTitle($this->view->translate('Лига'));
    }

    public function idAction()
    {
	$request = $this->getRequest();
	$league_id = (int) $request->getParam('league_id');

	$league = new Application_Model_DbTable_League();
	$league_data = $league->getLeagueData($league_id);

	if ($league_data) {
	    $this->view->league = $league_data;
	    $this->view->headTitle($league_data->name);
	    $this->view->pageTitle($league_data->name);

	    $championship = new Application_Model_DbTable_Championship();

	    $page_count_items = 5;
	    $page_range = 5;
	    $items_order = 'DESC';
	    $page = $request->getParam('page');

	    //add breadscrumb
	    $this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, $page);

	    $this->view->paginator = $championship->getChampionshipsPagerByLeague($page_count_items, $page, $page_range, $items_order, $league_id);
	} else {
	    $this->messageManager->addError($this->view->translate('Запрашиваемая лига не существует!'));

	    $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не существует!')}");
	    $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не существует!')}");
	}
    }

    public function addAction()
    {
	$this->view->headTitle($this->view->translate('Добавить'));
	$this->view->pageTitle($this->view->translate('Добавить лигу'));

	$request = $this->getRequest();
	// form
	$form = new Application_Form_League_Add();

	if ($this->getRequest()->isPost()) {
	    if ($form->isValid($request->getPost())) {

		$league_data = array();

		if ($form->getValue('logo')) {
		    if ($form->logo->receive()) {
			$file = $form->logo->getFileInfo();
			$ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
			$newName = Date('Y-m-d_H-i-s') . strtolower('_league_logo' . '.' . $ext);

			$filterRename = new Zend_Filter_File_Rename(array('target'
			    => $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

			$filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

			$league_data['url_logo'] = '/img/data/logos/leagues/' . $newName;
		    }
		}

		$date = date('Y-m-d H:i:s');

		$league_data['name'] = $form->getValue('name');
		$league_data['description'] = $form->getValue('description');
		$league_data['user_id'] = $form->getValue('admin');
		$league_data['date_create'] = $date;
		$league_data['date_edit'] = $date;

		$league = new Application_Model_DbTable_League();
		$newLeague = $league->createRow($league_data);
		$newLeague->save();

		$this->redirect($this->view->url(array('controller' => 'league', 'action' => 'id', 'league_id' => $newLeague->id), 'leagueIdAll', true));
	    } else {
		$this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
	    }
	}

	$user = new Application_Model_DbTable_User();

	$users_data = $user->getUsersByRoleName('admin', 'ASC');

	if ($users_data) {
	    foreach ($users_data as $user) {
		$form->admin->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
	    }
	    $this->view->form = $form;
	} else {
	    $this->messageManager->addError($this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы создать лигу.'));
	}
    }

    public function editAction()
    {
	$request = $this->getRequest();
	$league_id = $request->getParam('league_id');

	$league = new Application_Model_DbTable_League();
	$league_data = $league->fetchRow(array('id = ?' => $league_id));

	if (count($league_data) != 0) {
	    $this->view->headTitle($this->view->translate('Редактировать'));
	    $this->view->headTitle($league_data->name);

	    $this->view->pageTitle($this->view->translate('Редактировать лигу') . '::' . $league_data->name);

	    // form
	    $form = new Application_Form_League_Edit();
	    $form->setAction($this->view->url(array('controller' => 'league', 'action' => 'edit', 'league_id' => $league_id), 'league', true));
	    $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'league', 'action' => 'id', 'league_id' => $league_id), 'leagueIdAll', true)}\"");

	    if ($this->getRequest()->isPost()) {
		if ($form->isValid($request->getPost())) {

		    $new_league_data = array();

		    if ($form->getValue('logo')) {
			if ($form->logo->receive()) {
			    $file = $form->logo->getFileInfo();
			    $ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
			    $newName = Date('Y-m-d_H-i-s') . strtolower('_league_logo' . '.' . $ext);

			    $filterRename = new Zend_Filter_File_Rename(array('target'
				=> $file['logo']['destination'] . '/' . $newName, 'overwrite' => true));

			    $filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

			    $new_league_data['url_logo'] = '/img/data/logos/leagues/' . $newName;

			    if ($new_league_data['url_logo'] != $league_data['url_logo']) {
				unlink(APPLICATION_PATH . '/../public_html' . $league_data['url_logo']);
			    }
			}
		    }

		    $date = date('Y-m-d H:i:s');
		    $new_league_data['name'] = $form->getValue('name');
		    $new_league_data['description'] = $form->getValue('description');
		    $new_league_data['user_id'] = $form->getValue('admin');
		    $new_league_data['date_edit'] = $date;

		    $league_where = $league->getAdapter()->quoteInto('id = ?', $league_id);
		    $league->update($new_league_data, $league_where);

		    $this->redirect($this->view->url(array('controller' => 'league', 'action' => 'id', 'league_id' => $league_id), 'leagueIdAll', true));
		} else {
		    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
		}
	    }

	    $user = new Application_Model_DbTable_User();

	    $users_data = $user->getUsersByRoleName('admin', 'ASC');

	    if ($users_data) {
		foreach ($users_data as $user) {
		    $form->admin->addMultiOption($user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')');
		}
		$this->view->form = $form;
	    } else {
		$this->messageManager->addError($this->view->translate('Администраторы на сайте не найдены! Создайте администратора, чтобы редактировать лигу.'));
	    }

	    $form->name->setvalue($league_data->name);
	    $form->description->setvalue($league_data->description);
	    $form->admin->setvalue($league_data->user_id);

	    $this->view->form = $form;
	} else {
	    $this->messageManager->addError($this->view->translate('Лига не существует!'));
	    $this->view->headTitle($this->view->translate('Ошибка!'));
	    $this->view->headTitle($this->view->translate('Лига не существует!'));

	    $this->view->pageTitle($this->view->translate('Ошибка! Лига не существует!'));
	}
    }

    public function deleteAction()
    {
	// action body
    }

    public function allAction()
    {
	$this->view->headTitle($this->view->translate('Все Лиги'));
	$this->view->pageTitle($this->view->translate('Все Лиги'));

	// pager settings
	$page_count_items = 10;
	$page_range = 5;
	$items_order = 'ASC';
	$page = $this->getRequest()->getParam('page');

	$this->view->breadcrumb()->LeagueAll($page);

	$league = new Application_Model_DbTable_League();
	$paginator = $league->getLeaguePager($page_count_items, $page, $page_range, $items_order);

	if (count($paginator)) {
	    $this->view->paginator = $paginator;
	} else {
	    $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не существует!')}");
	}
    }

}