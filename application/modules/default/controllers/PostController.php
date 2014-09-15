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
            $postResult = $query->fetchArray();

            if (count($postResult) == 1) {

                // Update post views counting
                // TODO: Add coocie for counting views for this post
                if ($postResult[0]['LastUserIP'] != $_SERVER['REMOTE_ADDR']) {

                    $updatedPost = Doctrine_Core::getTable('Default_Model_Post')->find($requestData->postID);

                    $postResult[0]['Views'] = ++$postResult[0]['Views'];

                    $newPostData =array(
                        'Views' => $postResult[0]['Views'],
                        'LastUserIP' => $_SERVER['REMOTE_ADDR']
                    );

                    $updatedPost->fromArray($newPostData);
                    $updatedPost->save();
                }

                $this->view->postData = $postResult[0];

                $this->view->headTitle($postResult[0]['Name']);
                $this->view->pageTitle($postResult[0]['Name']);

                // Get post comments
                $query = Doctrine_Query::create()
                    ->from('Default_Model_Comment c')
                    ->leftJoin('c.User u')
                    ->where('c.PostID = ?', $requestData->postID);
                $commentResult = $query->fetchArray();

                $this->view->postCommentData = $commentResult;

                // Add CommentAdd Form
                $commentAddForm = new Peshkov_Form_Comment_Add();
                $this->view->commentAddForm = $commentAddForm;

                //add breadscrumb
                $this->view->breadcrumb()->PostAll('1')->Post($postResult[0]['ID'], $postResult[0]['Name']);
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
