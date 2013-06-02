<?php

class App_View_Helper_Breadcrumb extends Zend_View_Helper_Abstract {

    private $_breadcrumb_pages;
    private $_add_pages;

    public function breadcrumb() {
        if (!is_array($this->_add_pages)) {
            $this->_add_pages = array(
                array(
                    'pages' => array()
                )
            );
        }
        $this->_breadcrumb_pages = array(
            array(
                // Я обворачиваю текст в _(), чтобы потом вытянуть его парсером gettext'а
                'label' => _('Главная'),
                'controller' => 'index',
                'action' => 'index',
                'route' => 'default',
                'pages' => array(
                    array(
                        'controller' => 'user',
                        'action' => 'all',
                        'label' => _('Гонщики'),
                        'title' => _('Гонщики'),
                        'route' => 'userAll',
                        'pages' => array(
                        )
                    ),
                    array(
                        'label' => _('Новости'),
                        'title' => _('Новости'),
                        'controller' => 'post',
                        'action' => 'all',
                        'route' => 'postAll',
                        'pages' => array(
                        )
                    ),
                    array(
                        'label' => _('Файлы'),
                        'title' => _('Файлы'),
                        'uri' => '',
                        'pages' => array(
                            array(
                                'label' => _('Игры и Моды'),
                                'controller' => 'post',
                                'action' => 'all-by-type',
                                'route' => 'postAllByType',
                                'params' => array(
                                    'post_type_id' => '3'
                                ),
                                'pages' => array(
                                    array(
                                        'label' => _('Игра'),
                                        'title' => _('Игра'),
                                        'controller' => 'post',
                                        'action' => 'id',
                                        'route' => 'post',
                                        'params' => array()
                                    )
                                )
                            ),
                        )
                    ),
                    array(
                        'label' => _('Форум'),
                        'title' => _('Форум'),
                        'uri' => 'http://f1orl.forum2x2.ru/',
                    ),
                    array(
                        'label' => _('Чат'),
                        'title' => _('Чат'),
                        'controller' => 'chat',
                        'action' => 'index',
                        'route' => 'default',
                    ),
                    array(
                        'label' => _('Админ. панель'),
                        'title' => _('Панель администратора'),
                        'controller' => 'admin',
                        'action' => 'index',
                        'route' => 'default',
                        'pages' => array(
                        )
                    ),
                )
            ),
        );

        return $this;
    }

    public function Post($post_id, $post_title) {
        $pages = array(
            array(
                'label' => $post_title,
                'title' => $post_title,
                'uri' => $this->view->url(array('controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'postId', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'][0]['pages'] = $pages;
        return $this;
    }

    public function PostAll($page) {
        $pages = array(
            array(
                'label' => _('Контент сайта'),
                'title' => _('Контент сайта'),
                'uri' => $this->view->url(array('controller' => 'post', 'action' => 'all', 'page' => $page), 'postAll', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'] = $pages;
        return $this;
    }

    public function User($user_id, $user_login) {
        $pages = array(
            array(
                'label' => $user_login,
                'title' => $user_login,
                'uri' => $this->view->url(array('controller' => 'user', 'action' => 'id', 'user_id' => $user_id), 'userId', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'][0]['pages'] = $pages;
        return $this;
    }

    public function UserAll($page) {
        $pages = array(
            array(
                'label' => _('Гонщики'),
                'title' => _('Гонщики'),
                'uri' => $this->view->url(array('controller' => 'user', 'action' => 'all', 'page' => $page), 'userAll', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'] = $pages;
        return $this;
    }

    public function Championship_Team($league_id, $championship_id, $team_id, $team_name) {
        $pages = array(
            array(
                'label' => $team_name,
                'title' => $team_name,
                'uri' => $this->view->url(array('controller' => 'championship', 'action' => 'team-show', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'][0]['pages'][0]['pages'][0]['pages'][0]['pages'] = $pages;
        return $this;
    }

    public function Drivers($league_id, $championship_id) {
        $pages = array(
            array(
                'label' => 'Гонщики',
                'title' => 'Гонщики',
                'uri' => $this->view->url(array('controller' => 'championship', 'action' => 'drivers', 'league_id' => $league_id, 'championship_id' => $championship_id), 'championship', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'][0]['pages'][0]['pages'][0]['pages'] = $pages;
        return $this;
    }

    public function Championship($league_id, $championship_id, $championship_name, $page) {
        $pages = array(
            array(
                'label' => $championship_name,
                'title' => $championship_name,
                'uri' => $this->view->url(array('controller' => 'championship', 'action' => 'id', 'league_id' => $league_id, 'championship_id' => $championship_id, 'page' => $page), 'championshipId', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'][0]['pages'][0]['pages'] = $pages;
        return $this;
    }

    public function League($league_id, $league_name, $page) {
        $pages = array(
            array(
                'label' => $league_name,
                'title' => $league_name,
                'uri' => $this->view->url(array('controller' => 'league', 'action' => 'id', 'league_id' => $league_id, 'page' => $page), 'leagueIdAll', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'][0]['pages'] = $pages;
        return $this;
    }

    public function LeagueAll($page) {
        $pages = array(
            array(
                'label' => _('Все лиги'),
                'title' => _('Все лиги'),
                'uri' => $this->view->url(array('controller' => 'league', 'action' => 'all', 'page' => $page), 'leagueAll', true),
                'pages' => array()
            )
        );

        $this->_add_pages[0]['pages'] = $pages;
        return $this;
    }

    public function build() {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->init();

        if (count($this->_add_pages)) {
            $this->_breadcrumb_pages[0]['pages'] = $this->_add_pages[0]['pages'];
        }

        $breadcrumb_container = new Zend_Navigation($this->_breadcrumb_pages);
        $viewRenderer->view->breadcrumb = $breadcrumb_container;
    }

}