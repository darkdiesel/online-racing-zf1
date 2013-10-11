<?php

class App_View_Helper_ConfigureBlockItemMenu extends Zend_View_Helper_Abstract {

    private $_menu_html;
    private $_menu_empty;
    private $_links;

    public function configureBlockItemMenu($menu_type) {
        $this->_links = array();
        $this->_menu_empty = "";
        $this->_menu_html = "";

        $this->_menu_html = "<div class=\"configure-block-item-links\">";
        $this->_menu_html .= "<a class=\"configure-block-item-link\" data-toggle=\"dropdown\" href=\"#\" >";
        $this->_menu_html .= "<i class=\"icon-cog icon-black\"></i>";
        $this->_menu_html .= "<b class=\"icon-chevron-down\"></b>";
        $this->_menu_html .= "</a>";
        $this->_menu_html .= "<ul class=\"dropdown-menu\" role=\"menu\">";
        $this->_menu_html .= "<li class=\"dropdown-header\">{$menu_type}</li>";
        $this->_menu_html .= "<li class=\"divider\"></li>";

        return $this;
    }

    public function post_menu($post_id) {
        if ($this->view->checkUserAccess('default/post/edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'edit', 'post_id' => $post_id), 'post', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");

            $link = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'delete', 'post_id' => $post_id), 'post', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Удалить')}</a>");
        }

        return $this;
    }

    public function championship_menu($league_id, $championship_id) {
        if ($this->view->checkUserAccess('default/championship/team-add')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team-add', 'league_id' => $league_id, 'championship_id' => $championship_id), 'championship', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Добавить команду')}</a>");
        }

        if ($this->view->checkUserAccess('default/championship/edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'edit', 'league_id' => $league_id, 'championship_id' => $championship_id), 'championship', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
        }

        if ($this->view->checkUserAccess('default/championship/delete')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'delete', 'league_id' => $league_id, 'championship_id' => $championship_id), 'championship', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Удалить')}</a>");
        }

        if ($this->view->checkUserAccess('default/race/add')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'add', 'league_id' => $league_id, 'championship_id' => $championship_id), 'championshipRace', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Добавить гонку')}</a>");
        }

        return $this;
    }

    public function championship_race_menu($race_id) {

        return $this;
    }

    public function championship_team_menu($league_id, $championship_id, $team_id) {
        if ($this->view->checkUserAccess('default/championship/team-edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team-edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
        }

        if ($this->view->checkUserAccess('default/championship/driver-add')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-add', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Добавить гонщика')}</a>");
        }

        return $this;
    }

    public function championship_team_driver_menu($league_id, $championship_id, $team_id, $user_id) {
        if ($this->view->checkUserAccess('default/championship/driver-edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id, 'user_id' => $user_id), 'championshipTeamDriver', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Редактировать гонщика')}</a>");
        }

        if ($this->view->checkUserAccess('default/championship/driver-delete')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-delete', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id, 'user_id' => $user_id), 'championshipTeamDriver', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Удалить гонщика')}</a>");
        }

        return $this;
    }

    public function league_menu($league_id) {
        if ($this->view->checkUserAccess('default/championship/add')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'add', 'league_id' => $league_id), 'leagueActionAddChamp', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Добавить чемпионат')}</a>");
        }

        if ($this->view->checkUserAccess('default/league/edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'edit', 'league_id' => $league_id), 'league', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
        }

        if ($this->view->checkUserAccess('default/league/delete')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'delete', 'league_id' => $league_id), 'league', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Удалить')} (не реализовано)</a>");
        }

        return $this;
    }

    public function render() {
        if (count($this->_links) != 0) {
            foreach ($this->_links as $link) {
                $this->_menu_html .= "<li>";
                $this->_menu_html .= $link;
                $this->_menu_html .= "</li>";
            }

            $this->_menu_html .= "</ul>";
            $this->_menu_html .= "</div>";

            return $this->_menu_html;
        } else {
            return $this->_menu_empty;
        }
    }

    public function __toString() {
        return $this->render();
    }

}