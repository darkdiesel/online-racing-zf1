<?php

class Admin_CountryController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Страна'));
	}

	public function idAction() {
		$request = $this->getRequest();
		$country_id = (int) $request->getParam('country_id');

		$country = new Application_Model_DbTable_Country();
		$country_data = $country->getItem($country_id);

		if ($country_data) {
			$this->view->country = $country_data;
			$this->view->headTitle($country_data->native_name);
			$this->view->pageTitle("{$this->view->translate('Страна')} :: {$country_data->english_name} ({$country_data->native_name})");
		} else {
			$this->messages->addError("{$this->view->translate('Запрашиваемая страна не найдена!!')}");
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Страна не найдена!')");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Страна не найдена!')");
		}
	}

	public function allAction() {
		$this->view->headTitle($this->view->translate('Все страны'));
		$this->view->pageTitle($this->view->translate('Все страны'));

		$country = new Application_Model_DbTable_Country();

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$paginator = $country->getAll(FALSE, "all", "ASC", TRUE, $pager_args);

		if (count($paginator)) {
			$this->view->paginator = $paginator;
		} else {
			$this->messages->addError("{$this->view->translate('Запрашиваемыt страны на сайте не найден!')}");
		}
	}

	public function addAction() {
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить страну'));

		$request = $this->getRequest();
		// form
		$form = new Application_Form_Country_Add();
		$form->setAction(
				$this->view->url(
						array('module' => 'admin', 'controller' => 'country', 'action' => 'add'), 'default', true
				)
		);

		$county_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'country', 'action' => 'all'), 'country_all', true);
		$form->cancel->setAttrib('onClick', "location.href='{$county_all_url}'");

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {

				$country_data = array();

				//receive and rename image_round file
				if ($form->getValue('image_round')) {
					if ($form->image_round->receive()) {
						$file = $form->image_round->getFileInfo();
						$ext = pathinfo($file['image_round']['name'], PATHINFO_EXTENSION);
						$newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

						$filterRename = new Zend_Filter_File_Rename(array('target'
							=> $file['image_round']['destination'] . '/' . $newName, 'overwrite' => true));

						$filterRename->filter($file['image_round']['destination'] . '/' . $file['image_round']['name']);

						$country_data['url_image_round'] = '/img/data/flags/' . $newName;
					}
				}

				//receive and rename image_glossy_wave file
				if ($form->getValue('image_glossy_wave')) {
					if ($form->image_glossy_wave->receive()) {
						$file = $form->image_glossy_wave->getFileInfo();
						$ext = pathinfo($file['image_glossy_wave']['name'], PATHINFO_EXTENSION);
						$newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

						$filterRename = new Zend_Filter_File_Rename(array('target'
							=> $file['image_glossy_wave']['destination'] . '/' . $newName, 'overwrite' => true));

						$filterRename->filter($file['image_glossy_wave']['destination'] . '/' . $file['image_glossy_wave']['name']);

						$country_data['url_image_glossy_wave'] = '/img/data/flags/' . $newName;
					}
				}

				$date = date('Y-m-d H:i:s');
				$country_data['native_name'] = $form->getValue('native_name');
				$country_data['english_name'] = $form->getValue('english_name');
				$country_data['abbreviation'] = $form->getValue('abbreviation');
				$country_data['date_create'] = $date;
				$country_data['date_edit'] = $date;

				$country = new Application_Model_DbTable_Country();
				$newCountry = $country->createRow($country_data);
				$newCountry->save();

				$this->redirect($this->view->url(array('controller' => 'country', 'action' => 'id', 'id' => $newCountry->id), 'country', true));
			}
		}

		$this->view->form = $form;
	}

	public function editAction() {
		$request = $this->getRequest();
		$country_id = (int) $request->getParam('country_id');

		$this->view->headTitle($this->view->translate('Редактировать'));

		$country = new Application_Model_DbTable_Country();
		$country_data = $country->getItem($country_id);

		if ($country_data) {
			//create form and set some parameters
			$form = new Application_Form_Country_Edit();
			$form->setAction($this->view->url(
							array('module' => 'admin', 'controller' => 'country', 'action' => 'edit',
						'country_id' => $country_id), 'country_action', true
			));
			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'country', 'action' => 'id', 'country_id' => $country_id), 'country_id', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {

					$exist_country_native_name = $country->checkExistCountryNativeName($form->getValue('native_name'));
					$exist_country_abbreviation = $country->checkExistCountryAbbreviation($form->getValue('abbreviation'));

					if ($exist_country_native_name) {
						if ($exist_country_native_name == $country_id) {
							$update = TRUE;
						} else {
							$update = FALSE;
						}
					} else {
						$update = TRUE;
					}

					if ($update) {
						if ($exist_country_abbreviation) {
							if ($exist_country_abbreviation == $country_id) {
								$update = TRUE;
							} else {
								$update = FALSE;
							}
						} else {
							$update = TRUE;
						}
					}

					if ($update) {
						$new_country_data = array();

						//receive and rename first file 
						if ($form->getValue('image_round')) {
							if ($form->image_round->receive()) {
								$file = $form->image_round->getFileInfo();
								$ext = pathinfo($file['image_round']['name'], PATHINFO_EXTENSION);
								$newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

								$filterRename = new Zend_Filter_File_Rename(array('target'
									=> $file['image_round']['destination'] . '/' . $newName, 'overwrite' => true));

								$filterRename->filter($file['image_round']['destination'] . '/' . $file['image_round']['name']);

								$new_country_data['url_image_round'] = '/data-content/data-uploads/flags/' . $newName;

								if ($new_country_data['url_image_round'] != $country_data['url_image_round']) {
									unlink(APPLICATION_PATH . '/../public_html' . $country_data['url_image_round']);
								}
							}
						}

						if ($form->getValue('image_glossy_wave')) {
							if ($form->image_glossy_wave->receive()) {
								$file = $form->image_glossy_wave->getFileInfo();
								$ext = pathinfo($file['image_glossy_wave']['name'], PATHINFO_EXTENSION);
								$newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

								$filterRename = new Zend_Filter_File_Rename(array('target'
									=> $file['image_glossy_wave']['destination'] . '/' . $newName, 'overwrite' => true));

								$filterRename->filter($file['image_glossy_wave']['destination'] . '/' . $file['image_glossy_wave']['name']);

								$new_country_data['url_image_glossy_wave'] = '/data-content/data-uploads/flags/' . $newName;

								if ($new_country_data['url_image_glossy_wave'] != $country_data['url_image_glossy_wave']) {
									unlink(APPLICATION_PATH . '/../public_html' . $country_data['url_image_glossy_wave']);
								}
							}
						}

						$date = date('Y-m-d H:i:s');
						$new_country_data['native_name'] = $form->getValue('native_name');
						$new_country_data['english_name'] = $form->getValue('english_name');
						$new_country_data['abbreviation'] = $form->getValue('abbreviation');
						$new_country_data['date_edit'] = $date;

						$country_where = $country->getAdapter()->quoteInto('id = ?', $country_id);
						$country->update($new_country_data, $country_where);

						$this->redirect($this->view->url(array('controller' => 'country', 'action' => 'id', 'country_id' => $country_id), 'country_id', true));
					} else {
						$this->messages->addError($this->view->translate('Неверное имя страны или аббревиация!') . ": {$form->getValue('native_name')} , {$form->getValue('abbreviation')}");
						$this->messages->addError($this->view->translate('Имя страны или аббревиатура уже существуют в базе данных!'));
					}
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			//head titles
			$this->view->headTitle("{$country_data->native_name} ({$country_data->english_name})");
			$this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$country_data->native_name} ({$country_data->english_name})");

			//form values
			$form->native_name->setvalue($country_data->native_name);
			$form->english_name->setvalue($country_data->english_name);
			$form->abbreviation->setvalue($country_data->abbreviation);

			//get form for views
			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая страна не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Страна не найдена!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Страна не найдена!')}");
		}
	}

	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));

		$request = $this->getRequest();
		$country_id = (int) $request->getParam('country_id');

		$country_data = $this->db->get('country')->getItem($country_id);
		
		if ($country_data) {
			//page title
			$this->view->headTitle("{$country_data->native_name} ({$country_data->english_name})");
			$this->view->pageTitle("{$this->view->translate('Удалить страну')} :: {$country_data->native_name} ({$country_data->english_name})");

			$this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить страну')} <strong>\"{$country_data->native_name} ({$country_data->english_name})\"</strong> ?");

			$country_delete_url = $this->view->url(array('module' => 'admin', 'controller' => 'contry', 'action' => 'delete', 'country_id' => $country_id), 'country_action', true);
			$country_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'country', 'action' => 'id', 'country_id' => $country_id), 'country_id', true);
			
			//Create country delete form
			$form = new Application_Form_Country_Delete();
			$form->setAction($country_delete_url);
			$form->cancel->setAttrib('onClick', "location.href='{$country_id_url}'");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					$country_where = $this->db->get('country')->getAdapter()->quoteInto('id = ?', $country_id);
					$this->db->get('country')->delete($country_where);
					
					$this->messages->clearMessages();
					$this->messages->addSuccess("{$this->view->translate("Страна <strong>\"{$country_data->native_name} ({$country_data->english_name})\"</strong> успешно удалена")}");

					$country_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'country', 'action' => 'all', 'page' => 1), 'country_all', true);
					$this->redirect($country_all_url);
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->country = $country_data;
			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемая страна не найдена!'));
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Страна не найдена!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Страна не найдена!')}");
		}
	}

}
