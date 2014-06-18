<?php

class CommentController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
    }

    public function addAction(){
        // disable layout for this action
        $this->_helper->layout->disableLayout();

        $request = $this->getRequest();

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

                $post_id_url = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'post_id' => $comment_add_form->getValue('post_id')), 'defaultPostId', true);
                $this->redirect($post_id_url);
            }
        }
    }

}