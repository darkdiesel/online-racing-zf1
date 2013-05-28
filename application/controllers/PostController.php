<?php

class PostController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Контент'));
    }

    // action for view post
    public function idAction() {
        $request = $this->getRequest();
        $post_id = (int) $request->getParam('post_id');

        $post = new Application_Model_DbTable_Post();
        $post_data = $post->getPostData($post_id);

        if ($post_data) {
            $this->view->post = $post_data;
            $this->view->headTitle($post_data->title);
            $this->view->pageTitle($post_data->title);
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый контент не существует!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Контент не существует!')}");
        }
    }

    // action for view all posts
    public function allAction() {
        $this->view->headTitle($this->view->translate('Весь контент сайта'));
        $this->view->pageTitle($this->view->translate('Контент сайта'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $post = new Application_Model_DbTable_Post();
        $paginator = $post->getPublishedPostsPager($page_count_items, $page, $page_range, $items_order);

        if (count($paginator)) {
            $this->view->paginator = $paginator;
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
        }
    }

    // action for add new post
    public function addAction() {
        // page title
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить контент'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Post_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                // save new post to db
                $date = date('Y-m-d H:i:s');
                $post_data = array(
                    'user_id' => Zend_Auth::getInstance()->getStorage('online-racing')->read()->id,
                    'article_type_id' => $form->getValue('article_type'),
                    'content_type_id' => $form->getValue('content_type'),
                    'title' => $form->getValue('title'),
                    'annotation' => $form->getValue('annotation'),
                    'text' => $form->getValue('text'),
                    'image' => $form->getValue('image'),
                    'publish' => $form->getValue('publish'),
                    'publish_to_slider' => $form->getValue('publish_to_slider'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $post = new Application_Model_DbTable_Post();
                $newPost = $post->createRow($post_data);
                $newPost->save();

                $article_type = new Application_Model_DbTable_ArticleType();
                $article_type_name = $article_type->getName($form->getValue('article_type'));

                // save additional information corespondig article_type to db
                switch ($article_type_name) {
                    case 'game':
                        $game = new Application_Model_DbTable_Game();
                        $game_data = array(
                            'name' => $form->getValue('title'),
                            'post_id' => $newPost->id
                        );
                        $newGame = $game->createRow($game_data);
                        $newGame->save();
                        break;
                    default :
                        break;
                }

                $this->redirect($this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $newPost->id), 'postId', true));
            } else {
                $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        // add post types to the form
        $article_types = new Application_Model_DbTable_ArticleType();
        $article_types = $article_types->getArticleTypeNames('ASC');

        if ($article_types) {
            foreach ($article_types as $type):
                $form->article_type->addMultiOption($type->id, $type->name);

                if (strtolower($type->name) == 'game') {
                    $form->article_type->setvalue($type->id);
                }
            endforeach;
        } else {
            $this->messageManager->addError("{$this->view->translate('Типы статей на сайте не найдены!')}"
                    . "<br/><a class=\"btn btn-danger btn-small\" href=\"{$this->view->url(array('controller' => 'article-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
        }

        // add content types to the form
        $content_type = new Application_Model_DbTable_ContentType();
        $content_types = $content_type->getContentTypeNames('ASC');

        if ($content_types) {
            foreach ($content_types as $type):
                $form->content_type->addMultiOption($type->id, $type->name);
            endforeach;
        } else {
            $this->messageManager->addError("{$this->view->translate('Типы контента на сайте не найдены!')}"
                    . "<br/><a class=\"btn btn-danger btn-small\" href=\"{$this->view->url(array('controller' => 'content-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
        }

        $this->view->form = $form;
    }

    // action for edit post
    public function editAction() {
        $request = $this->getRequest();
        $post_id = (int) $request->getParam('post_id');
        $this->view->headTitle($this->view->translate('Редактировать'));

        $post = new Application_Model_DbTable_Post();
        $post_data = $post->getPostData($post_id);

        if ($post_data) {
            $form = new Application_Form_Post_Edit();
            $form->setAction($this->view->url(array('controller' => 'post', 'action' => 'edit', 'post_id' => $post_id), 'post', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'postId', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    if ($post_data->article_type_id == $form->getValue('article_type')) {
                        // if article type not changed do this code
                        $new_post_data = array(
                            'article_type_id' => $form->getValue('article_type'),
                            'content_type_id' => $form->getValue('content_type'),
                            'annotation' => $form->getValue('annotation'),
                            'title' => $form->getValue('title'),
                            'text' => $form->getValue('text'),
                            'image' => $form->getValue('image'),
                            'publish' => $form->getValue('publish'),
                            'publish_to_slider' => $form->getValue('publish_to_slider'),
                            'date_edit' => date('Y-m-d H:i:s'),
                        );
                        $post_where = $post->getAdapter()->quoteInto('id = ?', $post_id);
                        $post->update($new_post_data, $post_where);

                        $article_type = new Application_Model_DbTable_ArticleType();
                        $article_type_name = $article_type->getName($form->getValue('article_type'));

                        // save additional information corespondig article_type to db
                        switch ($article_type_name) {
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

                        $this->redirect($this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'postId', true));
                    } else {
                        // if post type changed
                        $this->messageManager->addError("{$this->view->translate('Функционал для смены типов статьи не готов.')}");
                    }
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            // add article types to the form
            $article_types = new Application_Model_DbTable_ArticleType();
            $article_types = $article_types->getArticleTypeNames('ASC');

            if ($article_types) {
                foreach ($article_types as $type):
                    $form->article_type->addMultiOption($type->id, $type->name);
                endforeach;
            } else {
                $this->messageManager->addError("{$this->view->translate('Типы статей на сайте не найдены!')}" .
                        "<br/><a href=\"{$this->view->url(array('controller' => 'article-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
            }

            // add content types to the form
            $content_type = new Application_Model_DbTable_ContentType();
            $content_types = $content_type->getContentTypeNames('ASC');

            if ($content_types) {
                foreach ($content_types as $type):
                    $form->content_type->addMultiOption($type->id, $type->name);
                endforeach;
            } else {
                $this->messageManager->addError("{$this->view->translate('Типы контента на сайте не найдены!')}" .
                        "<br/><a href=\"{$this->view->url(array('controller' => 'content-type', 'action' => 'add'), 'default', true)}\">{$this->view->translate('Создать?')}</a>");
            }

            //head titles
            $this->view->headTitle("{$post_data->title}");
            $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$post_data->title}");

            $form->title->setvalue($post_data->title);
            $form->article_type->setvalue($post_data->article_type_id);
            $form->content_type->setvalue($post_data->content_type_id);
            $form->annotation->setvalue($post_data->annotation);
            $form->text->setvalue($post_data->text);
            $form->image->setvalue($post_data->image);
            $form->publish->setvalue($post_data->publish);
            $form->publish_to_slider->setvalue($post_data->publish_to_slider);

            $this->view->form = $form;
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент не существует!')}");
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Контент не существует!')}");
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

            $this->messageManager->addWarning("{$this->view->translate('Вы действительно хотите удалить контент')} <strong>\"{$post_data->title}\"</strong> ?");

            //create delete form
            $form = new Application_Form_Post_Delete();
            $form->setAction($this->view->url(array('controller' => 'post', 'action' => 'delete', 'post_id' => $post_id), 'post', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'postId', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    $post_where = $post->getAdapter()->quoteInto('id = ?', $post_id);
                    $post->delete($post_where);

                    $article_type = new Application_Model_DbTable_ArticleType();
                    $article_type->getName($post_data->article_type_id);

                    switch ($article_type->name) {
                        case 'game':
                            $game = new Application_Model_DbTable_Game();
                            $game_where = $game->getAdapter()->quoteInto('id = ?', $game_id);
                            $game->delete($game_where);
                            break;
                        case 'news':
                            break;
                        default :
                            break;
                    }

                    $this->view->showMessages()->clearMessages();
                    $this->messageManager->addSuccess("{$this->view->translate("Статья <strong>\"{$post_data->title}\"</strong> успешно удалена")}");

                    $this->_helper->redirector('all', 'post');
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->post = $post_data;
            $this->view->form = $form;
        } else {
            $this->messageManager->addError("{$this->view->translate('Зарпашиваемый контент не найден!')}");
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Контент не существует!')");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Контент не существует!')}");
        }
    }

    public function allByTypeAction() {
        $request = $this->getRequest();
        $article_type_id = (int) $request->getParam('article_type_id');

        $article_type = new Application_Model_DbTable_ArticleType();
        $article_type_data = $article_type->getName($article_type_id);

        if ($article_type_data) {
            $this->view->headTitle("{$this->view->translate('Тип контента')} :: {$article_type_data}");
            $this->view->pageTitle("{$this->view->translate('Тип контента')} :: {$article_type_data}");

            // setup pager settings
            $page_count_items = 10;
            $page_range = 5;
            $items_order = 'DESC';
            $page = $this->getRequest()->getParam('page');

            $post = new Application_Model_DbTable_Post();
            $paginator = $post->getAllPostsPagerByType($page_count_items, $page, $page_range, $article_type_id, $items_order);

            if (count($paginator)) {
                $this->view->paginator = $paginator;
            } else {
                $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
            }

            $this->view->article_type_name = $article_type_data;
        } else {
            $this->messageManager->addError("{$this->view->translate('Зарпашиваемый тип контента не существует!')}");

            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не существует!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип контента не существует!')}");
        }
    }

}