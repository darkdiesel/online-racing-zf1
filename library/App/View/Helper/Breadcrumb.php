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
				'title' => _('Главная'),
				'module' => 'default',
				'controller' => 'index',
				'action' => 'index',
				'route' => 'default',
				'pages' => array(
					array(
						'label' => _('Гонщики'),
						'title' => _('Гонщики'),
						'module' => 'default',
						'controller' => 'user',
						'action' => 'all',
						'route' => 'userAll',
						'pages' => array(
						)
					),
					array(
						'label' => _('Новости'),
						'title' => _('Новости'),
						'module' => 'default',
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
								'title' => _('Игры и Моды'),
								'module' => 'default',
								'controller' => 'post',
								'action' => 'all-by-type',
								'route' => 'postAllByType',
								'params' => array(
									'post_type_id' => '3'
								),
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
						'module' => 'default',
						'controller' => 'chat',
						'action' => 'index',
						'route' => 'default',
					),
					array(
						'label' => _('Админ. панель'),
						'title' => _('Панель администратора'),
						'module' => 'admin',
						'controller' => 'index',
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'post_id' => $post_id), 'defaultPostId', true),
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'all', 'page' => $page), 'defaultPostAll', true),
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'id', 'user_id' => $user_id), 'defaultUserId', true),
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'all', 'page' => $page), 'defaultUserAll', true),
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true),
				'pages' => array()
			)
		);

		$this->_add_pages[0]['pages'][0]['pages'][0]['pages'][0]['pages'][0]['pages'] = $pages;
		return $this;
	}

	public function Championship_Race($league_id, $championship_id, $race_id, $race_name) {
		$pages = array(
			array(
				'label' => $race_name,
				'title' => $race_name,
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceId', true),
				'pages' => array()
			)
		);


		$this->_add_pages[0]['pages'][0]['pages'][0]['pages'][0]['pages'] = $pages;
		return $this;
	}

	public function Drivers($league_id, $championship_id) {
		$pages = array(
			array(
				'label' => 'Гонщики',
				'title' => 'Гонщики',
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'drivers', 'league_id' => $league_id, 'championship_id' => $championship_id), 'championship', true),
				'pages' => array()
			)
		);

		$this->_add_pages[0]['pages'][0]['pages'][0]['pages'][0]['pages'] = $pages;
		return $this;
	}

	public function Championship($league_id, $championship_id, $championship_name) {
		$pages = array(
			array(
				'label' => $championship_name,
				'title' => $championship_name,
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'id', 'league_id' => $league_id, 'championship_id' => $championship_id), 'defaultChampionshipId', true),
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'id', 'league_id' => $league_id, 'page' => $page), 'defaultLeagueIdAll', true),
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
				'uri' => $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'all', 'page' => $page), 'defaultLeagueAll', true),
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
