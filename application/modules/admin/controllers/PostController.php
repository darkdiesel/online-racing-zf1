<?php

class Admin_PostController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Контент'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view all posts
    public function allAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'page' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'page' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $this->view->headTitle($this->view->translate('Все'));
            $this->view->pageTitle($this->view->translate('Контент сайта'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Post p')
                ->leftJoin('p.User u')
                ->leftJoin('p.ContentType ct')
                ->leftJoin('p.PostType pt')
                ->orderBy('p.ID DESC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $postPaginator = new Zend_Paginator($adapter);
            // pager settings
            $postPaginator->setItemCountPerPage("10");
            $postPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $postPaginator->setPageRange("5");

            if ($postPaginator->count() == 0) {
                $this->view->postData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->postData = $postPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new post
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить контент'));

        // form
        $postAddForm = new Peshkov_Form_Post_Add();
        $this->view->postAddForm = $postAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($postAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Post();

                $item->fromArray($postAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                //receive and rename image_round file
                if ($postAddForm->getValue('ImageUrl')) {
                    if ($postAddForm->ImageUrl->receive()) {
                        $file = $postAddForm->ImageUrl->getFileInfo();
                        $ext = pathinfo($file['ImageUrl']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['ImageUrl']['destination'] . '/'
                            . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['ImageUrl']['destination'] . '/' . $file['ImageUrl']['name']
                        );

                        $item->ImageUrl = '/data-content/data-uploads/posts/' . $newName;
                    }
                }

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'default', 'controller' => 'post', 'action' => 'id',
                            'postID' => $item->ID), 'defaultPostID'
                    )
                );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->translate('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit post
    public function editAction()
    {
        $request = $this->getRequest();
        $post_id = (int)$request->getParam('post_id');
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
            $post_types_data = $this->db->get('post_type')->getAll(FALSE, array("ID", "Name"), "ASC");

            if ($post_types_data) {
                foreach ($post_types_data as $post_type):
                    $form->post_type->addMultiOption($post_type->ID, $post_type->Name);

                    if (strtolower($post_type->Name) == 'news') {
                        $form->post_type->setvalue($post_type->ID);
                    }
                endforeach;
            } else {
                $this->messages->addError($this->view->translate('Типы постов на сайте не найдены! Добавьте тип поста перед добавлением поста.'));
            }

            // add content types to the form
            $content_types_data = $this->db->get('content_type')->getAll(FALSE, array("id", "name"), "ASC");

            if ($content_types_data) {
                foreach ($content_types_data as $content_type):
                    $form->content_type->addMultiOption($content_type->ID, $content_type->Name);

                    if (strtolower($content_type->Name) == 'full html') {
                        $form->content_type->setvalue($content_type->ID);
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
