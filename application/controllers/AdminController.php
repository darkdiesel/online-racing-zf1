<?php
class AdminController extends Zend_Controller_Action
{

    public function init()
    {
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/admin.css"));
    }

    public function indexAction()
    {

    }
    
    public function articlesAction()
    {
        $this->view->headTitle($this->view->translate('Контент'));

        $request = $this->getRequest();
        $mapper = new Application_Model_ArticleMapper();
        $this->view->paginator = $mapper->getArticlesPager(10, $request->getParam('page'), 5,1,'admin_all');
    }
    
    public function usersAction()
    {

    }

}