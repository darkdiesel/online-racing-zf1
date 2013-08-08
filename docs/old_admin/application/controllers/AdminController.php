<?php

class AdminController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/admin.css"));
    }

    public function indexAction() {
        $this->view->headTitle($this->view->translate('Панель управления сайтом'));
    }

    public function adminAction() {
        $this->view->headTitle($this->view->translate('Панель управления сайтом для администратора'));
    }

    public function articlesAction() {
        $this->view->headTitle($this->view->translate('Контент сайта'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $post = new Application_Model_DbTable_Post();
        $this->view->paginator = $post->getAllArticlesPager($page_count_items, $page, $page_range, $items_order);
    }

    public function leaguesAction() {
        $this->view->headTitle($this->view->translate('Лиги'));

        $request = $this->getRequest();
        $mapper = new Application_Model_LeagueMapper();
        $this->view->paginator = $mapper->getLeaguesPager(10, $request->getParam('page'), 5, 'admin_all', 'ASC');
    }

    public function usersAction() {
        $this->view->headTitle($this->view->translate('Гонщики'));
        // pager settings
        $page_count_items = 9;
        $page = $this->getRequest()->getParam('page');
        $page_range = 5;
        $items_order = 'ASC';

        $user = new Application_Model_DbTable_User();
        $this->view->paginator = $user->getAllUsersPager($page_count_items, $page, $page_range, $items_order);
    }

}