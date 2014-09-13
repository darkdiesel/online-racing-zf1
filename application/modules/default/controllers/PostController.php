<?php

class PostController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Контент'));
    }

    // action for view post
    public function idAction()
    {
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
                ->where('p.ID = ?', $requestData->postID)
                ->andWhere('p.Publish = ?', 1);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->postData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle($result[0]['Name']);

                //get posts comment
                //TODO: Update comment model to Doctrine1
                $comment_idencity_args = array('post_id' => $result[0]['ID']);
                $postCommentData = $this->db->get('comment')->getAll($comment_idencity_args);

                //create and setup comment_add form
                $commentAddForm = new Application_Form_Comment_Add();
                $commentAddForm->setAction($this->view->url(array('controller' => 'comment', 'action' => 'add'), 'default', true));

                $commentAddForm->post_id->setvalue($result[0]['ID']);

                $this->view->postCommentData = $postCommentData;
                $this->view->commentAddForm = $commentAddForm;

                //add breadscrumb
                $this->view->breadcrumb()->PostAll('1')->Post($result[0]['ID'], $result[0]['Name']);
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемый пост не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Пост не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Пост не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
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
            $this->view->pageTitle($this->view->translate('Контент'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Post p')
                ->leftJoin('p.User u')
                ->leftJoin('p.ContentType ct')
                ->leftJoin('p.PostCategory pt')
                ->where('p.Publish = ?', 1)
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

    // action for view all posts
    public function byTypeAction()
    {
        // set render file as for all action
        $this->_helper->viewRenderer->setRender('all');

        // set filters and validators for GET input
        $filters = array(
            'page' => array('HtmlEntities', 'StripTags', 'StringTrim'),
            'postCategoryID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'page' => array('NotEmpty', 'Int'),
            'postCategoryID' => array('NotEmpty', 'Int')
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
                ->where('pt.ID = ?', $requestData->postCategoryID)
                ->andWhere('p.Publish = ?', 1)
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
                $item = $postPaginator->getItem(0);
                $this->view->headTitle($this->view->translate('Категория') . ' :: ' . $item['PostCategory']['Name']);
                $this->view->pageTitle($this->view->translate('Категория') . ' :: ' . $item['PostCategory']['Name']);

                $this->view->postData = $postPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
