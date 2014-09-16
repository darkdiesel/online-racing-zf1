<?php

class CommentController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Комментарий'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for add new post type
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить комментарий'));

        // form
        $commentAddForm = new Peshkov_Form_Comment_Add();
        $this->view->commentAddForm = $commentAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($commentAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Comment() ;

                $item->fromArray($commentAddForm->getValues());
                $item->UserID = Zend_Auth::getInstance()->getStorage()->read()->id;
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $defaultPostIDUrl = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'postID' => $commentAddForm->getValue('PostID')), 'defaultPostID', true);
                $this->redirect($defaultPostIDUrl);

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-category', 'action' => 'id',
                            'postCategoryID' => $item->ID), 'adminPostCategoryID'
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
}