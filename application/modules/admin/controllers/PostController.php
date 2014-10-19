<?php

class Admin_PostController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Контент'));

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
            $this->view->headTitle($this->view->t('Все'));
            $this->view->pageTitle($this->view->t('Контент'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Post p')
                ->leftJoin('p.User u')
                ->leftJoin('p.ContentType ct')
                ->leftJoin('p.PostCategory pt')
                ->orderBy('p.ID DESC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $postPaginator = new Zend_Paginator($adapter);
            // pager settings
            $postPaginator->setItemCountPerPage("10");
            $postPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $postPaginator->setPageRange("5");

            if ($postPaginator->count() == 0) {
                $this->view->postData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
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
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить контент'));

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

                $newPost = new Default_Model_Post();

                $storage = Zend_Auth::getInstance()->getStorage()->read();

                $newPost->fromArray($postAddForm->getValues());
                $newPost->UserID = $storage['UserID'];
                $newPost->DateCreate = $date;
                $newPost->DateEdit = $date;

                //receive and rename post image file
                //TODO: Uncoment this code for allow upload to server post image
//                if ($postAddForm->getValue('')) {
//                    if ($postAddForm->->receive()) {
//                        $file = $postAddForm->->getFileInfo();
//                        $ext = pathinfo($file['']['name'], PATHINFO_EXTENSION);
//                        $newName = Date('Y-m-d_H-i-s') . strtolower('_post_image' . '.' . $ext);
//
//                        $filterRename = new Zend_Filter_File_Rename(array('target'
//                        => $file['']['destination'] . '/'
//                            . $newName, 'overwrite' => true));
//
//                        $filterRename->filter(
//                            $file['']['destination'] . '/' . $file['']['name']
//                        );
//
//                        $newPost-> = '/data-content/data-uploads/posts/' . $newName;
//                    }
//                }

                $newPost->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'default', 'controller' => 'post', 'action' => 'id',
                            'postID' => $newPost->ID), 'defaultPostID'
                    )
                );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit post
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать пост'));

        // set filters and validators for GET input
        $filters = array(
            'postID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $postEditForm = new Peshkov_Form_Post_Edit();
            $this->view->postEditForm = $postEditForm;

            if ($this->getRequest()->isPost()) {
                if ($postEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $postEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_Post')->find($requestData->postID);

                    //receive and rename post image file
                    //TODO: Uncoment this code for allow upload to server post image
//                    if ($formData['ImageUrl']) {
//                        if ($postEditForm->ImageUrl->receive()) {
//                            $file = $postEditForm->ImageUrl->getFileInfo();
//                            $ext = pathinfo($file['ImageUrl']['name'], PATHINFO_EXTENSION);
//                            $newName = Date('Y-m-d_H-i-s') . strtolower('_post_image' . '.' . $ext);
//
//                            $filterRename = new Zend_Filter_File_Rename(array('target'
//                            => $file['ImageUrl']['destination'] . '/'
//                                . $newName, 'overwrite' => true));
//
//                            $filterRename->filter(
//                                $file['ImageUrl']['destination'] . '/' . $file['ImageUrl']['name']
//                            );
//
//                            $formData['ImageUrl'] = '/data-content/data-uploads/posts/' . $newName;
//
//                            if ($formData['ImageUrl'] != $item['ImageUrl']) {
//                                unlink(APPLICATION_PATH . '/../public_html' . $item['ImageUrl']);
//                            }
//                        }
//                    } else {
//                        unset($formData['ImageUrl']);
//                    }

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminPostIDUrl = $this->view->url(
                        array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'postID' => $requestData->postID),
                        'defaultPostID'
                    );

                    $this->redirect($adminPostIDUrl);
                } else {
                    $this->messages->addError(
                        $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                    );
                }
            } else {
                // if GET request
                // retrieve requested record
                // pre-populate form
                $query = Doctrine_Query::create()
                    ->from('Default_Model_Post p')
                    ->leftJoin('p.User u')
                    ->leftJoin('p.ContentType ct')
                    ->leftJoin('p.PostCategory pt')
                    ->where('p.ID = ?', $requestData->postID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->postData = $result[0];
                    $this->view->postEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемый пост не найден!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Пост не найден!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Пост не найден!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete racing series
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить пост'));

        // set filters and validators for GET input
        $filters = array(
            'postID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Post p')
                ->leftJoin('p.User u')
                ->leftJoin('p.ContentType ct')
                ->leftJoin('p.PostCategory pt')
                ->where('p.ID = ?', $requestData->postID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create post delete form
                $postDeleteForm = new Peshkov_Form_Post_Delete();

                $this->view->postData = $result[0];
                $this->view->postDeleteForm = $postDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить пост')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($postDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Post p')
                            ->whereIn('p.ID', $requestData->postID);

                        $result = $query->execute();

                        //TODO: Uncoment this code for allow upload to server post image
//                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->postData['ImageUrl']);

                        //TODO: Check that post not assign to any post

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t('Пост <strong>' . $this->view->postData['Name'] . '</strong> успешно удален.')
                        );

                        $adminPostAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'post', 'action' => 'all', 'page' => 1),
                            'adminPostAll'
                        );

                        $this->redirect($adminPostAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемый пост не найден!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Пост не найден!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Пост не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
