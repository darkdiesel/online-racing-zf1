<?php

class AdminController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/admin.css"));
    }

    public function indexAction() {
        $this->view->headTitle($this->view->translate('Панель управления сайтом'));
    }

    public function articlesAction() {
        $this->view->headTitle($this->view->translate('Контент'));

        $request = $this->getRequest();
        $mapper = new Application_Model_ArticleMapper();
        $this->view->paginator = $mapper->getArticlesPager(10, $request->getParam('page'), 5, 1, 'admin_all', 'DESC');
    }

    public function usersAction() {
        $this->view->headTitle($this->view->translate('Пользователи'));

        $request = $this->getRequest();
        $mapper = new Application_Model_UserMapper();

        $this->view->paginator = $mapper->getUsersPager(10, $request->getParam('page'), 5, 'admin_all', 'DESC');
    }

}