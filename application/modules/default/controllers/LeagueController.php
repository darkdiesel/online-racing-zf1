<?php

class LeagueController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Лига'));
	}

	public function idAction() {
		$request = $this->getRequest();
		$league_id = (int) $request->getParam('league_id');

		$league_data = $this->db->get('league')->getItem($league_id);

		if ($league_data) {
			$this->view->league_data = $league_data;
			$this->view->headTitle($league_data->name);
			$this->view->pageTitle($league_data->name);

			$championship = new Application_Model_DbTable_Championship();

			$page_count_items = 5;
			$page_range = 5;
			$items_order = 'DESC';
			$page = $request->getParam('page');

			//add breadscrumb
			$this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, $page);

			$championships_data = $championship->getChampionshipsPagerByLeague($page_count_items, $page, $page_range, $items_order, $league_id);

			if ($championships_data) {
				$this->view->championships_data = $championships_data;
			} else {
				$this->messages->addInfo($this->view->translate('В лиге не найдено чемпионатов!'));
			}
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая лига на сайте не найдена!'));

			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Запрашиваемая лига на сайте не найдена!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	public function allAction() {
		$this->view->headTitle($this->view->translate('Лиги Портала'));
		$this->view->pageTitle($this->view->translate('Лиги Портала'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$league_data = $this->db->get('league')->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

		if (count($league_data)) {
			$this->view->league_data = $league_data;
		} else {
			$this->messages->addInfo($this->view->translate('Запрашиваемые лиги на сайте не найдены!'));
		}
	}

}
