<?php

class RaceController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
//		$this->view->headTitle($this->view->translate('Гонка'));
	}

	public function idAction() {
		$request = $this->getRequest();
		$league_id = (int) $request->getParam('league_id');
		$championship_id = $request->getParam('championship_id');
		$race_id = $request->getParam('race_id');

		$league = new Application_Model_DbTable_League();
		$league_data = $league->getLeagueData($league_id);

		if ($league_data) {
			$championship = new Application_Model_DbTable_Championship();
			$championship_data = $championship->getChampionshipData($league_id, $championship_id);

			$this->view->headTitle($this->view->translate('Лига'));
			$this->view->headTitle($league_data->name);

			$this->view->league_data = $league_data;

			if ($championship_data) {
				$this->view->championship_data = $championship_data;
				$this->view->headTitle($this->view->translate('Чемпионат'));
				$this->view->headTitle($championship_data->name);
				$this->view->headTitle($this->view->translate('Гонка'));

				$race_data = $this->db->get('championship_race')->getItem(array(
					'id' => $race_id,
					'championship_id' => array('value' => $championship_id,
						'condition' => "AND")
				));

				if ($race_data) {
					$this->view->pageTitle($race_data->name);
					$this->view->race_data = $race_data;

					// Set breadcrumbs for this page
					$this->view->breadcrumb()->LeagueAll('1')->league($league_id, $league_data->name, '1')
							->championship($league_id, $championship_id, $championship_data->name, "1")
							->championship_race($league_id, $championship_id, $race_id, $race_data->name);
							//->championship_race($league_id, $championship_id, $race_id, $race_data->name);
				} else {
					$this->view->pageTitle($this->view->translate('Ошибка!'));
					$this->messages->addError($this->view->translate('Запрашиваемая гонка не найдена!'));
				}
			} else {
				//error message if championship not found
				$this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));
				$this->view->headTitle($this->view->translate('Ошибка!'));
				$this->view->pageTitle($this->view->translate('Ошибка!'));
			}
		} else {
			//error message if league not found
			$this->messages->addError($this->view->translate('Запрашиваемая лига не существует!'));
			//head title
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
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
			$this->view->league_data = $league_data;
			//Set titles for html head title
			$this->view->headTitle($this->view->translate('Лига'));
			$this->view->headTitle($league_data->name);

			$championship = new Application_Model_DbTable_Championship();
			$championship_data = $championship->getChampionshipData($league_id, $championship_id);

			if ($championship_data) {
				$this->view->championship_data = $championship_data;
				//Set titles for html head title
				$this->view->headTitle($this->view->translate('Чемпионат'));
				$this->view->headTitle($championship_data->name);

				$this->view->headTitle($this->view->translate('Добавить гонку'));
				// Set page title
				$this->view->pageTitle($this->view->translate('Добавить гонку'));

				$form = new Application_Form_Race_Add();

				$form->setAction($this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'add', 'league_id' => $league_id, 'championship_id' => $championship_id), 'defaultChampionshipRaceAction', true));

				if ($this->getRequest()->isPost()) {
					if ($form->isValid($request->getPost())) {
						$new_race_data = array();

						$date = date('Y-m-d H:i:s');

						$new_race_data['name'] = $form->getValue('name');
						$new_race_data['race_number'] = $form->getValue('race_number');
						if ($form->getValue('race_laps')) {
							$new_race_data['race_laps'] = $form->getValue('race_laps');
						} else {
							$new_race_data['race_laps'] = NULL;
						}
						$new_race_data['championship_id'] = $form->getValue('championship');
						$new_race_data['track_id'] = $form->getValue('track');
						$new_race_data['race_date'] = $form->getValue('race_date');
						$new_race_data['description'] = $form->getValue('description');
						$new_race_data['date_create'] = $date;
						$new_race_data['date_edit'] = $date;

						$newChampionshipRace = $this->db->get('championship_race')->createRow($new_race_data);
						$newChampionshipRace->save();

						$this->redirect($this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'id', 'league_id' => $league_data->id, 'championship_id' => $championship_data->id, 'race_id' => $newChampionshipRace->id), 'defaultChampionshipRaceId', true));
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
						$form->track->addMultiOption($track->id, $track->name . " ({$track->track_year})");
					endforeach;
				} else {
					$this->messages->addError($this->view->translate('Трассы не найдены!'));
				}

				$this->view->form = $form;
			} else {
				//error message if championship not found
				$this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));
				$this->view->headTitle($this->view->translate('Ошибка!'));
				$this->view->pageTitle($this->view->translate('Ошибка!'));
			}
		} else {
			//error message if league not found
			$this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
			//head title
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	public function editAction() {
		$request = $this->getRequest();
		$league_id = (int) $request->getParam('league_id');
		$championship_id = $request->getParam('championship_id');
		$race_id = $request->getParam('race_id');

		$league = new Application_Model_DbTable_League();
		$league_data = $league->getLeagueData($league_id);

		if ($league_data) {
			$this->view->league_data = $league_data;
			//Set titles for html head title
			$this->view->headTitle($this->view->translate('Лига'));
			$this->view->headTitle($league_data->name);

			$championship = new Application_Model_DbTable_Championship();
			$championship_data = $championship->getChampionshipData($league_id, $championship_id);

			if ($championship_data) {
				$this->view->championship_data = $championship_data;
				//Set titles for html head title
				$this->view->headTitle($this->view->translate('Чемпионат'));
				$this->view->headTitle($championship_data->name);

				$championship_race_data = $this->db->get('championship_race')->getItem(
						array(
					'id' => $race_id,
					'championship_id' =>
					array(
						'value' => $championship_data->id,
						'condition' => 'AND')
						), "id, name, description", "ASC"
				);

				if ($championship_race_data) {
					$this->view->championship_race_data = $championship_race_data;
					//Set titles for html head title
					$this->view->headTitle($this->view->translate('Гонка'));
					$this->view->headTitle($this->view->translate($championship_race_data->name));
					// Set page title
					$this->view->pageTitle($this->view->translate('Редактировать гонку'));

					$championship_race_id_url = $this->view->url(array('module' => 'deafult', 'controller' => 'race', 'action' => 'id', 'league_id' => $league_data->id, 'championship_id' => $championship_data->id, 'race_id' => $race_id), 'defaultChampionshipRaceId', true);

					$form = new Application_Form_Race_Edit();
					$form->setAction($this->view->url(array('module' => 'deafult', 'controller' => 'race', 'action' => 'edit', 'league_id' => $league_data->id, 'championship_id' => $championship_data->id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true));
					$form->cancel->setAttrib('onClick', "location.href=\"{$championship_race_id_url}\"");

					if ($this->getRequest()->isPost()) {
						if ($form->isValid($request->getPost())) {
							$date = date('Y-m-d H:i:s');

							$new_race_data['name'] = $form->getValue('name');
							$new_race_data['race_number'] = $form->getValue('race_number');
							if ($form->getValue('race_laps')) {
								$new_race_data['race_laps'] = $form->getValue('race_laps');
							} else {
								$new_race_data['race_laps'] = NULL;
							}
							$new_race_data['championship_id'] = $form->getValue('championship');
							$new_race_data['track_id'] = $form->getValue('track');
							$new_race_data['race_date'] = $form->getValue('race_date');
							$new_race_data['description'] = $form->getValue('description');
							$new_race_data['date_edit'] = $date;

							$championship_race_where = $this->db->get('championship_race')->getAdapter()->quoteInto("id = {$race_id} and championship_id = {$championship_id}");
							$this->db->get('championship_race')->update($new_race_data, $championship_race_where);

							$this->redirect($championship_race_id_url);
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
							$form->track->addMultiOption($track->id, $track->name . " ({$track->track_year})");
						endforeach;
					} else {
						$this->messages->addError($this->view->translate('Трассы не найдены!'));
					}

					// Fill form's fields
					$form->name->setvalue($championship_race_data->name);
					$form->race_number->setvalue($championship_race_data->race_number);
					$form->race_laps->setvalue($championship_race_data->race_laps);
					$form->race_date->setvalue($championship_race_data->race_date);
					$form->championship->setvalue($championship_race_data->championship_id);
					$form->track->setvalue($championship_race_data->track_id);
					$form->description->setvalue($championship_race_data->description);

					$this->view->form = $form;
				} else {
					//Set titles for html head title
					$this->view->headTitle($this->view->translate('Ошибка!'));
					// Set page title
					$this->view->pageTitle($this->view->translate('Ошибка!'));
					$this->messages->addError($this->view->translate('Запрашиваемая гонка не найдена!'));
				}
			} else {
				//error message if championship not found
				$this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));
				$this->view->headTitle($this->view->translate('Ошибка!'));
				$this->view->pageTitle($this->view->translate('Ошибка!'));
			}
		} else {
			//error message if league not found
			$this->messages->addError($this->view->translate('Запрашиваемая лига не найдена!'));
			//head title
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

}
