<?php

class CommentController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Комментарий'));
    }

    public function addAction(){
        $request = $this->getRequest();

        // Set head and page titles
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить комментарий'));

        //create comment_add form
        $comment_add_form = new Application_Form_Comment_Add();
        $comment_add_form->setAction($this->view->url(array('controller' => 'comment', 'action' => 'add'), 'default', true));

        if ($this->getRequest()->isPost()) {
            if ($comment_add_form->isValid($request->getPost())) {
                // create current date
                $date = date('Y-m-d H:i:s');

                //create array with new comment data
                $new_comment_data = array(
                    'user_id' => Zend_Auth::getInstance()->getStorage()->read()->id,
                    'post_id' => $comment_add_form->getValue('post_id'),
                    'title' => $comment_add_form->getValue('title'),
                    'content' => $comment_add_form->getValue('content'),
                    'date_create' => $date,
                    'date_edit' => $date,
                    'status' => 1,
                );

                $new_comment = $this->db->get('comment')->createRow($new_comment_data);
                $new_comment->save();

                $defaultPostIDUrl = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'postID' => $comment_add_form->getValue('post_id')), 'defaultPostID', true);
                $this->redirect($defaultPostIDUrl);
            }
        }

        $this->view->comment_add_form = $comment_add_form;
    }

}