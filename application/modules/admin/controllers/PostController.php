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

		$paginator = $this->db->get('post')->getAll(FALSE, "id, title", "DESC", TRUE, $pager_args);

		if (count($paginator)) {
			$this->view->post_data = $paginator;
		} else {
			$this->messages->addInfo("{$this->view->translate('Запрашиваемый контент на сайте не найдены!')}");
		}
	}

	// action for edit post
	public function editAction() {
		$request = $this->getRequest();
		$post_id = (int) $request->getParam('post_id');
		$this->view->headTitle($this->view->translate('Редактировать'));

		$post_data = $this->db->get('post')->getItem($post_id);

		if ($post_data) {
			$form = new Application_Form_Post_Edit();
			$form->setAction($this->view->url(array('controller' => 'post', 'action' => 'edit', 'post_id' => $post_id), 'defaultPostAction', true));
			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'defaultPostId', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {

					if ($post_data->post_type_id == $form->getValue('post_type')) {
						// if article type not changed do this code
						$new_post_data = array(
							'post_type_id' => $form->getValue('post_type'),
							'content_type_id' => $form->getValue('content_type'),
							'annotation' => $form->getValue('annotation'),
							'title' => $form->getValue('title'),
							'text' => $form->getValue('text'),
							'image' => $form->getValue('image'),
							'publish' => $form->getValue('publish'),
							'publish_to_slider' => $form->getValue('publish_to_slider'),
							'date_edit' => date('Y-m-d H:i:s'),
						);
						$post_where = $this->db->get('post')->getAdapter()->quoteInto('id = ?', $post_id);
						$this->db->get('post')->update($new_post_data, $post_where);

						$post_type_name = $this->db->get('post_type')->getItem($form->getValue('post_type'), array('id', 'name'));

						// save additional information corespondig post_type to db
						switch ($post_type_name) {
							case 'game':
								$game = new Application_Model_DbTable_Game();
								$game_data = array(
									'name' => $form->getValue('title'),
								);
								$game_where = $game->getAdapter()->quoteInto('post_id = ?', $post_id);
								$game->update($game_data, $game_where);
								break;
							default :

								break;
						}

						$this->redirect($this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'defaultPostId', true));
					} else {
						// if post type changed
						$this->messages->addError("{$this->view->translate('Функционал для смены типов статьи не готов.')}");
					}
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
				$this->messages->addError("{$this->view->translate('Типы статей на сайте не найдены!')}"
						. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'article-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
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
				$this->messages->addError("{$this->view->translate('Типы контента на сайте не найдены!')}"
						. "<br/><a class=\"btn btn-danger btn-sm\" href=\"{$this->view->url(array('controller' => 'content-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
			}

			//head titles
			$this->view->headTitle("{$post_data->title}");
			$this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$post_data->title}");

			$form->title->setvalue($post_data->title);
			$form->post_type->setvalue($post_data->post_type_id);
			$form->content_type->setvalue($post_data->content_type_id);
			$form->annotation->setvalue($post_data->annotation);
			$form->text->setvalue($post_data->text);
			$form->image->setvalue($post_data->image);
			$form->publish->setvalue($post_data->publish);
			$form->publish_to_slider->setvalue($post_data->publish_to_slider);

			$this->view->form = $form;
		} else {
			$this->messages->addError("{$this->view->translate('Запрашиваемый контент не существует!')}");
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
		}
	}

}
