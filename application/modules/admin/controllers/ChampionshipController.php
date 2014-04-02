<?php

class Admin_ChampionshipController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Чемпионат'));
	}

	public function allAction() {
		$this->view->headTitle($this->view->translate('Чемпионаты Портала'));
		$this->view->pageTitle($this->view->translate('Чемпионаты Портала'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$championship_data = $this->db->get('championship')->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

		if (count($championship_data)) {
			$this->view->championship_data = $championship_data;
		} else {
			$this->messages->addInfo($this->view->translate('Запрашиваемые чемпионаты на сайте не найдены!'));
		}
	}

	public function addAction() {
		$request = $this->getRequest();
		$league_id = (int) $request->getParam('league_id');

		$league_data = $this->db->get('league')->getItem($league_id);

		if ($league_data) {
			$this->view->headTitle("{$this->view->translate('Лига')} :: {$league_data->name}");
			$this->view->headTitle("{$this->view->translate('Чемпионат')} :: {$this->view->translate('Добавить')}");
			$this->view->pageTitle($this->view->translate('Добавить чемпионат'));

			$league_league_action_add_champ_url = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'add', 'league_id' => $league_id), 'defaultLeagueActionAddChamp', true);

			// form
			$form = new Application_Form_Championship_Add();
			$form->setAction($league_league_action_add_champ_url);

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {

					$championship_data = array();

					//receive and rename logo file
					if ($form->getValue('logo')) {
						if ($form->logo->receive()) {
							$file = $form->logo->getFileInfo();
							$ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
							$newName = Date('Y-m-d_H-i-s') . strtolower('_logo' . '.' . $ext);

							$filterRename = new Zend_Filter_File_Rename(array('target'
								=> $file['logo']['destination'] . '/'
								. $newName, 'overwrite' => true));

							$filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

							$championship_data['url_logo'] = '/data-content/data-uploads/championship/logo/' . $newName;
						}
					}

					// save new championship to db
					$date = date('Y-m-d H:i:s');

					$championship_data['name'] = $form->getValue('name');
					$championship_data['league_id'] = $form->getValue('league');
					$championship_data['rule_id'] = $form->getValue('rule');
					$championship_data['game_id'] = $form->getValue('game');
					$championship_data['user_id'] = $form->getValue('admin');
					$championship_data['date_start'] = $form->getValue('date_start');
					$championship_data['date_end'] = $form->getValue('date_end');
					$championship_data['hotlap_ip'] = $form->getValue('hotlap_ip');
					$championship_data['description'] = $form->getValue('description');
					$championship_data['date_create'] = $date;
					$championship_data['date_edit'] = $date;

					$championship = new Application_Model_DbTable_Championship();
					$newChampionship = $championship->createRow($championship_data);
					$newChampionship->save();
					$this->redirect(
							$this->view->url(
									array('controller' => 'championship', 'action' => 'id',
								'league_id' => $newChampionship->league_id,
								'championship_id' => $newChampionship->id), 'defaultChampionshipId', true
							)
					);
				} else {
					$this->messages->addError(
							$this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
					);
				}
			}
			// add league
			$form->league->addMultiOption($league_data->id, $league_data->name);
			$form->league->setValue($league_data->id);

			// add reglaments
			$post = new Application_Model_DbTable_Post();
			$posts = $post->getPublishPostTitlesByTypeName('rule', 'ASC');

			if ($posts) {
				foreach ($posts as $post):
					$form->rule->addMultiOption($post->id, $post->name);
				endforeach;
			} else {
				$this->messages->addError(
						"{$this->view->translate('Регламенты не найдены!')}"
						. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(
								array('controller' => 'post', 'action' => 'add'), 'default', true
						)}\">{$this->view->translate('Создать?')}</a>"
				);
			}

			// add games
			$post = new Application_Model_DbTable_Post();
			$posts = $post->getPublishPostTitlesByTypeName('game', 'ASC');

			if ($posts) {
				foreach ($posts as $game):
					$form->game->addMultiOption($game->id, $game->name);
				endforeach;
			} else {
				$this->messages->addError(
						"{$this->view->translate('Игры не найдены!')}"
						. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(
								array('controller' => 'post', 'action' => 'add'), 'default', true
						)}\">{$this->view->translate('Создать?')}</a>"
				);
			}

			// add admins
			$user = new Application_Model_DbTable_User();
			$users = $user->getUsersByRoleName('admin', 'ASC');

			if ($users) {
				foreach ($users as $user) {
					$form->admin->addMultiOption(
							$user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')'
					);
				}
			} else {
				$this->messages->addError("{$this->view->translate('Администраторы не найдены!')}");
			}

			$this->view->form = $form;
		} else {
			$this->messages->addError(
					$this->view->translate(
							'Запрашиваемая лига не найдена! Нельзя добавить чемпионат в несуществующую лигу'
					)
			);

			$this->view->headTitle(
					"{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
			);
			$this->view->pageTitle(
					"{$this->view->translate('Ошибка!')} :: {$this->view->translate('Лига не найдена!')}"
			);
		}
	}

	public function editAction() {
		$request = $this->getRequest();
		$championship_id = $request->getParam('championship_id');

		$championship_data = $this->db->get('championship')->getItem($championship_id);

		if ($championship_data) {
			$this->view->headTitle($championship_data->name);
			$this->view->headTitle($this->view->translate('Редактировать'));

			$form = new Application_Form_Championship_Edit();
			$this->view->pageTitle($championship_data->name);

			$default_championship_id_url = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'id', 'league_id' => $championship_data->league_id, 'championship_id' => $championship_id), 'defaultChampionshipId', true);
			$admin_championship_action_url = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'edit', 'id' => $championship_id), 'adminChampionshipAction', true);
			$admin_championship_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'all'), 'adminChampionshipAll', true);

			$form->setAction($admin_championship_action_url);
			$form->cancel->setAttrib('onClick', 'location.href="' . $admin_championship_all_url . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					//Check Existing championships by name 
					$exist_championship_name = $this->db->get('championship')->getItem(
							array(
						'name' => array(
							'value' => $form->getValue('name')
						)
							), 'id, name');

					if ($exist_championship_name) {
						if ($exist_championship_name->id == $championship_id) {
							$update = true;
						} else {
							$update = false;
						}
					} else {
						$update = true;
					}

					if ($update) {
						//saving new data to DB
						$new_championship_data = array();

						//receive and rename logo file
						if ($form->getValue('logo')) {
							if ($form->logo->receive()) {
								$file = $form->logo->getFileInfo();
								$ext = pathinfo($file['logo']['name'], PATHINFO_EXTENSION);
								$newName = Date('Y-m-d_H-i-s') . strtolower('_logo' . '.' . $ext);

								$filterRename = new Zend_Filter_File_Rename(array('target'
									=>
									$file['logo']['destination'] . '/'
									. $newName, 'overwrite' => true));

								$filterRename->filter($file['logo']['destination'] . '/' . $file['logo']['name']);

								$new_championship_data['url_logo'] = '/data-content/data-uploads/championship/logo/' . $newName;

								if ($new_championship_data['url_logo'] != $championship_data['url_logo']) {
									unlink(APPLICATION_PATH . '/../public_html' . $championship_data['url_logo']);
								}
							}
						}

						// save new championship to db
						$date = date('Y-m-d H:i:s');

						$new_championship_data['name'] = $form->getValue('name');
						$new_championship_data['league_id'] = $form->getValue('league');
						$new_championship_data['rule_id'] = $form->getValue('rule');
						$new_championship_data['game_id'] = $form->getValue('game');
						$new_championship_data['user_id'] = $form->getValue('admin');
						$new_championship_data['date_start'] = $form->getValue('date_start');
						$new_championship_data['date_end'] = $form->getValue('date_end');
						$new_championship_data['hotlap_ip'] = $form->getValue('hotlap_ip');
						$new_championship_data['description'] = $form->getValue('description');
						$new_championship_data['date_edit'] = $date;

						$championship_where = $this->db->get('championship')->getAdapter()->quoteInto('id = ?', $championship_id);
						$this->db->get('championship')->update($new_championship_data, $championship_where);

						$this->redirect($default_championship_id_url);
					} else {
						$this->messages->addError(
							$this->view->translate(sprintf('Название чемпионата "%s" уже существуют в базе данных!', $form->getValue('name')))
						);
						$this->messages->addError(
							$this->view->translate('Исправьте название чемпионата!')
						);
					}
				} else {
					$this->messages->addError(
							$this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
					);
				}
			}
			//set championship name value
			$form->name->setValue($championship_data->name);

			//set league value
			$league_data = $this->db->get('league')->getAll(FALSE, "id, name", "ASC");

			if ($league_data) {
				foreach ($league_data as $league):
					$form->league->addMultiOption($league->id, $league->name);
				endforeach;
			} else {
				$this->messages->addError($this->view->translate('Лиги на сайте не найдены!'));
				$this->messages->addWarnign($this->view->translate('Добавьте лигу, чтобы иметь возможность создания чемпионата!'));
			}

			$form->league->setValue($championship_data->league_id);

			// set reglament value
			/*
			 * TODO: Add request to get posts by post type
			 */
			$post_data = $this->db->get('post')->getAll(FALSE, "id, name", "ASC");

			if ($post_data) {
				foreach ($post_data as $post):
					$form->rule->addMultiOption($post->id, $post->name);
				endforeach;
			} else {
				$this->messages->addError($this->view->translate('Регламенты на сайте не найдены!'));
				$this->messages->addWarnign($this->view->translate('Добавьте регламент, чтобы иметь возможность создания чемпионата!'));
			}

			$form->rule->setValue($championship_data->rule_id);

			// set game value
			/*
			 * TODO: Add request to get posts by post type
			 */
			$post_data = $this->db->get('post')->getAll(FALSE, "id, name", "ASC");

			if ($post_data) {
				foreach ($post_data as $game):
					$form->game->addMultiOption($game->id, $game->name);
				endforeach;
			} else {
				$this->messages->addError($this->view->translate('Игры на сайте не найдены!'));
				$this->messages->addWarnign($this->view->translate('Добавьте игру, чтобы иметь возможность создания чемпионата!'));
			}

			$form->game->setValue($championship_data->game_id);

			// set championship admin value
			/*
			 * TODO: Add request to get users by user role
			 */

			$user_data = $this->db->get('user')->getAll(FALSE, "id, name", "ASC");

			if ($user_data) {
				foreach ($user_data as $user) {
					$form->admin->addMultiOption(
							$user->id, $user->surname . ' ' . $user->name . ' (' . $user->login . ')'
					);
				}
			} else {
				$this->messages->addError($this->view->translate('Администраторы на сайте не найдены!'));
				$this->messages->addWarnign($this->view->translate('Добавьте администратора, чтобы иметь возможность создания чемпионата!'));
			}

			$form->admin->setValue($championship_data->user_id);

			$form->date_start->setValue($championship_data->date_start);
			$form->date_end->setValue($championship_data->date_end);
			$form->hotlap_ip->setValue($championship_data->hotlap_ip);
			$form->description->setValue($championship_data->description);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемый чемпионат не найден!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Чемпионат не найден!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));

		$request = $this->getRequest();
		$championship_id = $request->getParam('championship_id');
	}

}