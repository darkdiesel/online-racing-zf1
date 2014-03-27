<?php

class App_View_Helper_BlockConfigMenu extends Zend_View_Helper_Abstract {

	private $_menuHtml;
	private $_emptyMenu;
	private $_menuLinks;
	private $_menuHeader;

	public function BlockConfigMenu($menuHeader) {
		$this->_menuLinks = array();
		$this->_emptyMenu = "";
		$this->_menuHtml = "";
		$this->_menuHeader = $menuHeader;

		return $this;
	}

	public function postMenu($post_id) {
		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'post', 'edit')) {
			$link = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'edit', 'post_id' => $post_id), 'adminPostAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'post', 'delete')) {
			$link = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'delete', 'post_id' => $post_id), 'adminPostAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Удалить')}</a>");
		}

		return $this;
	}

	public function championshipMenu($league_id, $championship_id) {


		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'edit')) {
			$link = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'edit', 'league_id' => $league_id, 'championship_id' => $championship_id), 'adminChampionshipAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'delete')) {
			$link = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'delete', 'league_id' => $league_id, 'championship_id' => $championship_id), 'adminChampionshipAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Удалить')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'team-add')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team-add', 'league_id' => $league_id, 'championship_id' => $championship_id), 'defaultChampionshipId', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Добавить команду')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'add')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'add', 'league_id' => $league_id, 'championship_id' => $championship_id), 'defaultChampionshipRaceAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Добавить гонку')}</a>");
		}

		return $this;
	}

	public function championshipRaceMenu($league_id, $championship_id, $race_id) {
		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'delete', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Удалить')}</a>");
		}

		return $this;
	}

	public function championshipTeamMenu($league_id, $championship_id, $team_id) {
		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'team-edit')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team-edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'defaultChampionshipTeam', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'driver-add')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-add', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'defaultChampionshipTeam', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Добавить гонщика')}</a>");
		}

		return $this;
	}

	public function championshipTeamDriverMenu($league_id, $championship_id, $team_id, $user_id) {
		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'driver-edit')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id, 'user_id' => $user_id), 'defaultChampionshipTeamDriver', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Редактировать гонщика')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'driver-delete')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-delete', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id, 'user_id' => $user_id), 'defaultChampionshipTeamDriver', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Удалить гонщика')}</a>");
		}

		return $this;
	}

	public function leagueMenu($league_id) {
		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'add')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'add', 'league_id' => $league_id), 'defaultLeagueActionAddChamp', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Добавить чемпионат')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'league', 'edit')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'edit', 'league_id' => $league_id), 'defaultLeagueAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
		}

		if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'league', 'delete')) {
			$link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'delete', 'league_id' => $league_id), 'defaultLeagueAction', true);
			array_push($this->_menuLinks, "<a href=\"$link\">{$this->view->translate('Удалить')} (не реализовано)</a>");
		}

		return $this;
	}

	private function buildMenu() {
		$this->_menuHtml = '<div class = "pull-right action-buttons block-config-menu">';
		$this->_menuHtml .= '<div class = "btn-group pull-right">';
		$this->_menuHtml .= '<button type = "button" class = "btn btn-default btn-xs dropdown-toggle block-config-btn" data-toggle = "dropdown">';
		$this->_menuHtml .= '<span class = "fa fa-cog fa-lg" style = "margin-right: 0px;"></span>';
		$this->_menuHtml .= '</button>';
		$this->_menuHtml .= '<ul class = "dropdown-menu slidedown">';
		if (!empty($this->_menuHeader)) {
			$this->_menuHtml .= '<li class="dropdown-header">' . $this->_menuHeader . '</li>';
			$this->_menuHtml .= '<li class="divider"></li>';
		}

		$links = "";

		foreach ($this->_menuLinks as $link) {
			$links .= "<li>";
			$links .= $link;
			$links .= "</li>";
		}

		$this->_menuHtml .= $links;
		$this->_menuHtml .= '</ul>';
		$this->_menuHtml .= '</div>';
		$this->_menuHtml .= '</div>';
	}

	public function render() {

		if (count($this->_menuLinks) > 0) {
			$this->buildMenu();

			return $this->_menuHtml;
		} else {
			return $this->_emptyMenu;
		}
	}

	public function __toString() {
		return $this->render();
	}

}
