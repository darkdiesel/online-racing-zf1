<?php

class Admin_PostCategoryController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Категория поста'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view post type
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'postCategoryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postCategoryID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_PostCategory pc')
                ->where('pc.ID = ?', $requestData->postCategoryID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->postCategoryData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->t('Категория поста') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая категория поста не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Категория поста не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Категория поста не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all post types
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
            $this->view->pageTitle($this->view->t('Категории постов'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_PostCategory pc')
                ->orderBy('pc.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $postCategoryPaginator = new Zend_Paginator($adapter);
            // pager settings
            $postCategoryPaginator->setItemCountPerPage("10");
            $postCategoryPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $postCategoryPaginator->setPageRange("5");

            if ($postCategoryPaginator->count() == 0) {
                $this->view->postCategoryData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->postCategoryData = $postCategoryPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new post type
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить категорию поста'));

        // form
        $postCategoryAddForm = new Peshkov_Form_PostCategory_Add();
        $this->view->postCategoryAddForm = $postCategoryAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($postCategoryAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_PostCategory();

                $item->fromArray($postCategoryAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-category', 'action' => 'id',
                            'postCategoryID' => $item->ID), 'adminPostCategoryID'
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

    // action for edit post type
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать категорию поста'));

        // set filters and validators for GET input
        $filters = array(
            'postCategoryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postCategoryID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $postCategoryEditForm = new Peshkov_Form_PostCategory_Edit();
            $this->view->postCategoryEditForm = $postCategoryEditForm;

            if ($this->getRequest()->isPost()) {
                if ($postCategoryEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $postCategoryEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_PostCategory')->find($requestData->postCategoryID);

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminPostCategoryIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-category', 'action' => 'id', 'postCategoryID' => $requestData->postCategoryID),
                        'adminPostCategoryID'
                    );

                    $this->redirect($adminPostCategoryIDUrl);
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
                    ->from('Default_Model_PostCategory pc')
                    ->where('pc.ID = ?', $requestData->postCategoryID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->postCategoryData = $result[0];
                    $this->view->postCategoryEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемая категория поста не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Категория поста не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Категория поста не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete post type
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить категорию поста'));

        // set filters and validators for GET input
        $filters = array(
            'postCategoryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postCategoryID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_PostCategory pc')
                ->where('pc.ID = ?', $requestData->postCategoryID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create post-category delete form
                $postCategoryDeleteForm = new Peshkov_Form_PostCategory_Delete();

                $this->view->postCategoryData = $result[0];
                $this->view->postCategoryDeleteForm = $postCategoryDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить категорию поста')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($postCategoryDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_PostCategory pc')
                            ->whereIn('pc.ID', $requestData->postCategoryID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Категория контента <strong>" . $this->view->postCategoryData['Name'] . "</strong> успешно удален."
                            )
                        );

                        $adminPostCategoryAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'post-category', 'action' => 'all', 'page' => 1),
                            'adminPostCategoryAll'
                        );

                        $this->redirect($adminPostCategoryAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая категория поста не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Категория поста не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Категория поста не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
