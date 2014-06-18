<?php

class PostController extends App_Controller_LoaderController {

	public function init() {
		parent::init();
		$this->view->headTitle($this->view->translate('Контент'));
	}

	// action for view post
	public function idAction() {
		$request = $this->getRequest();
		$post_id = (int) $request->getParam('post_id');

        //get post
		$post_data = $this->db->get('post')->getItem($post_id);

		if ($post_data) {
			//Set breadcrumb for this page
			$this->view->breadcrumb()->PostAll('1')->Post($post_id, $post_data->name);

			// Set head and page titles
			$this->view->headTitle($post_data->name);
			$this->view->pageTitle($post_data->name);

            //get posts comment
            $comment_idencity_args = array('post_id' => $post_id);
            $post_comment_data = $this->db->get('comment')->getAll($comment_idencity_args);

            //create and setup comment_add form
            $comment_add_form = new Application_Form_Comment_Add();
            $comment_add_form->setAction($this->view->url(array('controller' => 'comment', 'action' => 'add'), 'default', true));

            $comment_add_form->post_id->setvalue($post_id);

            $this->view->post_data = $post_data;
            $this->view->post_comment_data = $post_comment_data;
            $this->view->comment_add_form = $comment_add_form;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемый контент на сайте не найден!'));
			$this->view->headTitle($this->view->translate('Ошибка!'));
			$this->view->headTitle($this->view->translate('Контент не существует!'));
			$this->view->pageTitle($this->view->translate('Ошибка!'));
		}
	}

	// action for view all posts
	public function allAction() {
		$this->view->headTitle($this->view->translate('Контент сайта'));
		$this->view->pageTitle($this->view->translate('Контент сайта'));

		// pager settings
		$page_count_items = 10;
		$page_range = 10;
		$items_order = 'DESC';
		$page = $this->getRequest()->getParam('page');

		$this->view->breadcrumb()->PostAll($page);

		$post = new Application_Model_DbTable_Post();
		$paginator = $post->getPublishedPostsPager($page_count_items, $page, $page_range, $items_order);

		if (count($paginator)) {
			$this->view->paginator = $paginator;
		} else {
			$this->messages->addError($this->view->translate('Запрашиваемый контент на сайте не найден!'));
		}
	}

	// action for delete post
	public function deleteAction() {
		$this->view->headTitle($this->view->translate('Удалить'));

		$request = $this->getRequest();
		$post_id = (int) $request->getParam('post_id');

		$post = new Application_Model_DbTable_Post();
		$post_data = $post->getPostData($post_id);

		if ($post_data) {
			//page title
			$this->view->headTitle($post_data->title);
			$this->view->pageTitle("{$this->view->translate('Удалить контент')} :: {$post_data->title}");

			$this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить контент')} <strong>\"{$post_data->title}\"</strong> ?");

			//create delete form
			$form = new Application_Form_Post_Delete();
			$form->setAction($this->view->url(array('module' => 'default','controller' => 'post', 'action' => 'delete', 'post_id' => $post_id), 'defaultPostAction', true));
			$form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'defaultPostId', true)}\"");

			if ($this->getRequest()->isPost()) {
				if ($form->isValid($request->getPost())) {

					$post_where = $post->getAdapter()->quoteInto('id = ?', $post_id);
					$post->delete($post_where);

					$post_type = new Application_Model_DbTable_PostType();
					$post_type->getName($post_data->post_type_id);

					switch ($post_type->name) {
						case 'game':
							$game = new Application_Model_DbTable_Game();
							$game_where = $game->getAdapter()->quoteInto('id = ?', $post_id);
							$game->delete($game_where);
							break;
						case 'news':
							break;
						default :
							break;
					}

					$this->view->showMessages()->clearMessages();
					$this->messages->addSuccess("{$this->view->translate("Статья <strong>\"{$post_data->title}\"</strong> успешно удалена")}");

					$this->redirect($this->view->url(array('controller' => 'post', 'action' => 'all', 'page' => 1), 'defaultPostAll', true));
				} else {
					$this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
				}
			}

			$this->view->post = $post_data;
			$this->view->form = $form;
		} else {
			$this->messages->addError("{$this->view->translate('Зарпашиваемый контент не найден!')}");
			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Контент не существует!')}");
		}
	}

	public function byTypeAction() {
		$this->_helper->viewRenderer->setRender('all');

		$request = $this->getRequest();
		$post_type_id = (int) $request->getParam('post_type_id');

		$post_type_data = $this->db->get('post_type')->getItem($post_type_id);

		if ($post_type_data) {
			$this->view->headTitle("{$this->view->translate('Категория контента')} :: {$post_type_data->name}");
			$this->view->pageTitle("{$this->view->translate('Категория контента')} :: {$post_type_data->name}");

			// setup pager settings
			$page_count_items = 10;
			$page_range = 5;
			$items_order = 'DESC';
			$page = $this->getRequest()->getParam('page');

			$post = new Application_Model_DbTable_Post();
			$paginator = $post->getAllPostsPagerByType($page_count_items, $page, $page_range, $post_type_id, $items_order);

			if (count($paginator)) {
				$this->view->paginator = $paginator;
			} else {
				$this->messages->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
			}

			$this->view->post_type_name = $post_type_data;
		} else {
			$this->messages->addError("{$this->view->translate('Зарпашиваемый тип контента не существует!')}");

			$this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не существует!')}");
			$this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не существует!')}");
		}
	}

}
