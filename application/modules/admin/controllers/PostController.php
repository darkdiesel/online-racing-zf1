<?php

class Admin_PostController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Контент'));
	}

	// action for view all posts
	public function allAction() {
		$this->view->headTitle($this->view->translate('Контент сайта'));
		$this->view->pageTitle($this->view->translate('Контент сайта'));

		// pager settings
		$pager_args = array(
			"page_count_items" => 10,
			"page_range" => 5,
			"page" => $this->getRequest()->getParam('page')
		);

		$post_data = $this->db->get('post')->getAll(FALSE, "id, name", "DESC", TRUE, $pager_args);

		if (count($post_data)) {
			$this->view->post_data = $post_data;
		} else {
			$this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найдены!'));
		}
	}

	// action for add new post
	public function addAction() {
		// page title
		$this->view->headTitle($this->view->translate('Добавить'));
		$this->view->pageTitle($this->view->translate('Добавить контент'));

		$request = $this->getRequest();
		
		$post_add_url = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'add'), 'default', true);
		$post_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'all'), 'adminPostAll', true);
		
		// form
		$form = new Application_Form_Post_Add();
		$form->setAction($post_add_url);
		$form->cancel->setAttrib('onClick', 'location.href="' . $post_all_url . '"');

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($request->getPost())) {
				// save new post to db
				$date = date('Y-m-d H:i:s');
				$new_post_data = array(
					'user_id' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id,
					'post_type_id' => $form->getValue('post_type'),
					'content_type_id' => $form->getValue('content_type'),
					'name' => $form->getValue('name'),
					'preview' => $form->getValue('preview'),
					'text' => $form->getValue('text'),
					'image' => $form->getValue('image'),
					'publish' => $form->getValue('publish'),
					'publish_to_slider' => $form->getValue('publish_to_slider'),
					'date_create' => $date,
					'date_edit' => $date,
				);

				$newPost = $this->db->get('post')->createRow($new_post_data);
				$newPost->save();

				$post_id_url = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'post_id' => $newPost->id), 'defaultPostId', true);
				$this->redirect($post_id_url);
			} else {
				$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
			}
		}

		// add post types to the form
		$post_types_data = $this->db->get('post_type')->getAll(FALSE, array("id", "name"), "ASC");

		if ($post_types_data) {
			foreach ($post_types_data as $post_type):
				$form->post_type->addMultiOption($post_type->id, $post_type->name);

				if (strtolower($post_type->name) == 'news') {
					$form->post_type->setvalue($post_type->id);
				}
			endforeach;
		} else {
			$this->messages->addError($this->view->translate('Типы постов на сайте не найдены! Добавьте тип поста перед добавлением поста.'));
		}

		// add content types to the form
		$content_types_data = $this->db->get('content_type')->getAll(FALSE, array("id", "name"), "ASC");

		if ($content_types_data) {
			foreach ($content_types_data as $content_type):
				$form->content_type->addMultiOption($content_type->id, $content_type->name);

				if (strtolower($content_type->name) == 'full html') {
					$form->content_type->setvalue($content_type->id);
				}
			endforeach;
		} else {
			$this->messages->addError($this->view->translate('Типы контента на сайте не найдены! Добавьте тип контента перед добавлением поста.'));
		}

		$this->view->form = $form;
	}

	// action for edit post
	public function editAction() {
		$request = $this->getRequest();
		$post_id = (int) $request->getParam('post_id');
		$this->view->headTitle($this->view->translate('Редактировать'));

		$post_data = $this->db->get('post')->getItem($post_id);

		if ($post_data) {
			$post_edit_url = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'edit', 'post_id' => $post_id), 'adminPostAction', true);
			$post_id_url = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'defaultPostId', true);
			
			//form
			$form = new Application_Form_Post_Edit();
			$form->setAction($post_edit_url);
			$form->cancel->setAttrib('onClick', 'location.href="' . $post_id_url . '"');

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {
					// if article type not changed do this code
					$new_post_data = array(
						'name' => $form->getValue('name'),
						'post_type_id' => $form->getValue('post_type'),
						'content_type_id' => $form->getValue('content_type'),
						'preview' => $form->getValue('preview'),
						'text' => $form->getValue('text'),
						'image' => $form->getValue('image'),
						'publish' => $form->getValue('publish'),
						'publish_to_slider' => $form->getValue('publish_to_slider'),
						'date_edit' => date('Y-m-d H:i:s'),
					);

					$post_where = $this->db->get('post')->getAdapter()->quoteInto('id = ?', $post_id);
					$this->db->get('post')->update($new_post_data, $post_where);

					$this->redirect($post_id_url);
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			// add post types to the form
			$post_types_data = $this->db->get('post_type')->getAll(FALSE, array("id", "name"), "ASC");

			if ($post_types_data) {
				foreach ($post_types_data as $post_type):
					$form->post_type->addMultiOption($post_type->id, $post_type->name);

					if (strtolower($post_type->name) == 'news') {
						$form->post_type->setvalue($post_type->id);
					}
				endforeach;
			} else {
				$this->messages->addError($this->view->translate('Типы постов на сайте не найдены! Добавьте тип поста перед добавлением поста.'));
			}

			// add content types to the form
			$content_types_data = $this->db->get('content_type')->getAll(FALSE, array("id", "name"), "ASC");

			if ($content_types_data) {
				foreach ($content_types_data as $content_type):
					$form->content_type->addMultiOption($content_type->id, $content_type->name);

					if (strtolower($content_type->name) == 'full html') {
						$form->content_type->setvalue($content_type->id);
					}
				endforeach;
			} else {
				$this->messages->addError($this->view->translate('Типы контента на сайте не найдены! Добавьте тип контента перед добавлением поста.'));
			}

			//head titles
			$this->view->headTitle($post_data->name);
			$this->view->pageTitle($this->view->translate('Редактировать'));
			$this->view->pageTitle($post_data->name);

			$form->name->setvalue($post_data->name);
			$form->post_type->setvalue($post_data->post_type_id);
			$form->content_type->setvalue($post_data->content_type_id);
			$form->preview->setvalue($post_data->preview);
			$form->text->setvalue($post_data->text);
			$form->image->setvalue($post_data->image);
			$form->publish->setvalue($post_data->publish);
			$form->publish_to_slider->setvalue($post_data->publish_to_slider);

			$this->view->form = $form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемый контент на сайте не найден!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Запрашиваемый контент на сайте не найден'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

}
