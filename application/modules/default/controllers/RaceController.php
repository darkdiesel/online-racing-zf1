<?php

class RaceController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Гонка'));
	}

	public function idAction() {
		// action body
	}

	public function addAction() {
		// css and js for date time picker script
		$this->view->headLink()->appendStylesheet($this->view->baseUrl("css/jquery-ui-timepicker-addon.min.css"));
		$this->view->headScript()->appendFile($this->view->baseUrl("js/jquery-ui-timepicker-addon.min.js"));

		$request = $this->getRequest();
		$league_id = (int) $request->getParam('league_id');
		$championship_id = $request->getParam('championship_id');

		$league = new Application_Model_DbTable_League();
		$league_data = $league->getLeagueData($league_id);

		if ($league_data) {
			$championship = new Application_Model_DbTable_Championship();
			$championship_data = $championship->getChampionshipData($league_id, $championship_id);

			$this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
			$this->view->headTitle($this->view->translate('Чемпионат'));
			$this->view->league_data = $league_data;

			if ($championship_data) {
				$this->view->championship = $championship_data;
				$this->view->headTitle("{$this->view->translate('Чемпионат')} :: {$championship_data->name} :: {$this->view->translate('Добавить гонку')}");
				$this->view->pageTitle("{$championship_data->name} :: {$this->view->translate('Добавить гонку')}");

				$form = new Application_Form_Race_Add();
				$form->setAction($this->view->url(array('controller' => 'race', 'action' => 'add', 'championship_id' => $championship_id), 'championshipRace', true));

				if ($this->getRequest()->isPost()) {
					if ($form->isValid($request->getPost())) {
						$new_race_data = array();

						$date = date('Y-m-d H:i:s');

						$new_race_data['name'] = $form->getValue('name');
						$new_race_data['race_number'] = $form->getValue('race_number');
						$new_race_data['championship_id'] = $form->getValue('championship');
						$new_race_data['track_id'] = $form->getValue('track');
						$new_race_data['race_date'] = $form->getValue('race_date');
						$new_race_data['description'] = $form->getValue('description');
						$new_race_data['date_create'] = $date;
						$new_race_data['date_edit'] = $date;

						$race = new Application_Model_DbTable_ChampionshipRace();
						$newRace = $race->createRow($new_race_data);
						$newRace->save();
						
						$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'id', 'leagueid' => $league_data->id, 'championship_id' => $championship_data->id, 'race_id' => $newRace->id), 'championshipRaceId', true));
					} else {
						$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
					}
				}
				// add championship
				$form->championship->addMultiOption($championship_data->id, $championship_data->name);
				$form->championship->setValue($championship_data->id);

				// add tracks to the form
				$tracks_data = $this->db->get('track')->getAll(FALSE, array("id", "name"), "ASC");

				if ($tracks_data) {
					foreach ($tracks_data as $track):
						$form->track->addMultiOption($track->id, $track->name);
					endforeach;
				} else {
					$this->messages->addError("{$this->view->translate('Трассы не найдены!')}"
							. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'track', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
				}

				$this->view->form = $form;
			} else {
				//error message if championship not found
				$this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));
				$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Чемпионат не найден!')}");
				$this->view->pageTitle($this->view->translate('Ошибка!'));
			}
		} else {
			//error message if league not found
			$this->messages->addError($this->view->translate('Запрашиваемая лига не существует!'));
			//head title
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не существует!')}");
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

}
