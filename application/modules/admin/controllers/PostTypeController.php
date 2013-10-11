<?php

class Admin_PostTypeController extends App_Controller_LoaderController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Тип статьи'));
    }

    // action for view post type
    public function idAction() {
        $request = $this->getRequest();
        $post_type_id = (int) $request->getParam('id');

        $post_type_data = $this->db->get('post_type')->getItem($post_type_id);

        if ($post_type_data) {
            $this->view->post_type = $post_type_data;
            $this->view->headTitle($post_type_data->name);
            $this->view->pageTitle($post_type_data->name);
            return;
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип статьи не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип статьи не найден!')}");
        }
    }

    // action for view all post types
    public function allAction() {
        $this->view->headTitle($this->view->translate('Все'));
        $this->view->pageTitle($this->view->translate('Типы статей'));

        // pager settings
        $pager_args = array(
            "page_count_items" => 10,
            "page_range" => 5,
            "page" => $this->getRequest()->getParam('page')
        );

        $paginator = $this->db->get('post_type')->getAll(FALSE, "id, name, description", "ASC", TRUE, $pager_args);

        if (count($paginator)) {
            $this->view->paginator = $paginator;
        } else {
            $this->messageManager->addInfo("{$this->view->translate('Запрашиваемые типы статей на сайте не найдены!')}");
        }
    }

    // action for add new post type
    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить тип статьи'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_PostType_Add();
        $form->setAction(
                $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-type', 'action' => 'add'), 'default', true
                )
        );

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $post_type_data = array(
                    'name' => strtolower($form->getValue('name')),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $new_post_type = $this->db->get('post_type')->createRow($post_type_data);
                $new_post_type->save();

                $this->redirect(
                        $this->view->url(
                                array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id',
                            'post_type_id' => $new_post_type->id), 'post_type_id', true
                        )
                );
            } else {
                $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        $this->view->form = $form;
    }

    // action for edit post type
    public function editAction() {
        $request = $this->getRequest();
        $post_type_id = $request->getParam('id');

        $this->view->headTitle($this->view->translate('Редактировать'));

        $post_type_data = $this->db->get('post_type')->getItem($post_type_id);

        if ($post_type_data) {
            // form
            $form = new Application_Form_ContentType_Edit();
            $form->setAction($this->view->url(
                            array('module' => 'admin', 'controller' => 'post-type', 'action' => 'edit',
                        'post_type_id' => $post_type_id), 'post_type_action', true
            ));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'all'), 'default', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $new_post_type_data = array(
                        'name' => strtolower($form->getValue('name')),
                        'description' => $form->getValue('description'),
                        'date_edit' => date('Y-m-d H:i:s')
                    );

                    $post_type_where = $post_type->getAdapter()->quoteInto('id = ?', $post_type_id);
                    $post_type->update($new_post_type_data, $post_type_where);

                    $this->redirect($this->view->url(
                                    array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id',
                                'post_type_id' => $post_type_id), 'post_type_id', true
                    ));
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }
            $this->view->headTitle($post_type_data->name);
            $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$post_type_data->name}");

            $form->name->setvalue($post_type_data->name);
            $form->description->setvalue($post_type_data->description);

            $this->view->form = $form;
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип статьи не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип статьи не найден!')}");
        }
    }

    // action for delete post type
    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $post_type_id = (int) $request->getParam('post_type_id');

        $post_type_data = $this->db->get('post_type')->getItem($post_type_id);

        if ($post_type_data) {
            $this->view->headTitle($post_type_data->name);
            $this->view->pageTitle("{$this->view->translate('Удалить тип статьи')} :: {$post_type_data->name}");

            $this->messageManager->addWarning("{$this->view->translate('Вы действительно хотите удалить тип статьи')} <strong>\"{$post_type_data->name}\"</strong> ?");

            $form = new Application_Form_PostType_Delete();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'delete', 'post_type_id' => $post_type_id), 'post_type_action', true));
            $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id', 'post_type_id' => $post_type_id), 'post_type_id', true) . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $post_type_where = $this->db->get('post_type')->getAdapter()->quoteInto('id = ?', $post_type_id);
                    $this->db->get('post_type')->delete($post_type_where);

                    $this->view->showMessages()->clearMessages();
                    $this->messageManager->addSuccess("{$this->view->translate("Тип статьи <strong>\"{$post_type_data->name}\"</strong> успешно удален")}");

                    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'all', 'page' => 1), 'post_type_all', true));
                } else {
                    $this->messageManager->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->form = $form;
            $this->view->post_type = $post_type_data;
        } else {
            $this->messageManager->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип статьи не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип статьи не найден!')}");
        }
    }

}
