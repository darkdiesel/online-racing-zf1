<?php

class App_View_Helper_BlockConfigMenu extends Zend_View_Helper_Abstract
{

    private $_menuHtml;
    private $_emptyMenu;
    private $_menuLinks;
    private $_menuHeader;
    private $_blockMenu;

    private $_view_icon;
    private $_edit_icon;
    private $_delete_icon;

    public function BlockConfigMenu($menuHeader, $blockMenu = true)
    {
        $this->_menuLinks = array();
        $this->_emptyMenu = "";
        $this->_menuHtml = "";
        $this->_menuHeader = $menuHeader;
        $this->_blockMenu = $blockMenu;

        $this->_view_icon = '<i class="fa fa-eye"></i> ';
        $this->_edit_icon = '<i class="fa fa-pencil"></i> ';
        $this->_delete_icon = '<i class="fa fa-trash-o"></i> ';

        return $this;
    }

    public function postMenu($post_id)
    {
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

    public function championshipMenu($league_id, $championship_id)
    {


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

    public function championshipRaceMenu($league_id, $championship_id, $race_id, $track_id)
    {
        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true);
            array_push($this->_menuLinks, '<a href="' . $link . '">' . $this->view->translate('Редактировать гонку') . '</a>');
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'delete', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true);
            array_push($this->_menuLinks, '<a href="' . $link . '">' . $this->view->translate('Удалить гонку') . '</a>');
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'track', 'edit')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'edit', 'track_id' => $track_id), 'adminTrackAction', true);
            array_push($this->_menuLinks, '<a href="' . $link . '">' . $this->view->translate('Редактировать трассу') . '</a>');
        }

        return $this;
    }

    public function championshipTeamMenu($league_id, $championship_id, $team_id)
    {
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

    public function championshipTeamDriverMenu($league_id, $championship_id, $team_id, $user_id)
    {
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

    public function leagueMenu($league_id)
    {
        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'add')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'add'), 'default', true);
            array_push($this->_menuLinks, '<a href="' . $link . '">' . $this->view->translate('Добавить чемпионат') . '</a>');
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'league', 'edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'edit', 'league_id' => $league_id), 'defaultLeagueAction', true);
            array_push($this->_menuLinks, '<a href="' . $link . '">' . $this->view->translate('Редактировать') . '</a>');
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'league', 'delete')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'delete', 'league_id' => $league_id), 'defaultLeagueAction', true);
            array_push($this->_menuLinks, '<a href="' . $link . '">' . $this->view->translate('Удалить') . ' (не реализовано)' . '</a>');
        }

        return $this;
    }

    public function countryMenu($countryID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'country', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'country', 'action' => 'id', 'countryID' => $countryID), 'adminCountryID', true);
                array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>');
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'country', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'country', 'action' => 'edit', 'countryID' => $countryID), 'adminCountryAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>');
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'country', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'country', 'action' => 'delete', 'countryID' => $countryID), 'adminCountryAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>');
        }

        return $this;
    }

    public function contentTypeMenu($contentTypeID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'content-type', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'content-type', 'action' => 'id', 'contentTypeID' => $contentTypeID), 'adminContentTypeID', true);
                array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>');
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'content-type', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'content-type', 'action' => 'edit', 'contentTypeID' => $contentTypeID), 'adminContentTypeAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>');
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'content-type', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'content-type', 'action' => 'delete', 'contentTypeID' => $contentTypeID), 'adminContentTypeAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>');
        }

        return $this;
    }

    public function postTypeMenu($postTypeID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post-type', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'post-type', 'action' => 'id', 'postTypeID' => $postTypeID), 'adminPostTypeID', true);
                array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>');
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post-type', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'post-type', 'action' => 'edit', 'postTypeID' => $postTypeID), 'adminPostTypeAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>');
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post-type', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'post-type', 'action' => 'delete', 'postTypeID' => $postTypeID), 'adminPostTypeAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>');
        }

        return $this;
    }

    public function racingSeriesMenu($racingSeriesID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'racing-series', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'racing-series', 'action' => 'id', 'racingSeriesID' => $racingSeriesID), 'adminRacingSeriesID', true);
                array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>');
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'racing-series', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'racing-series', 'action' => 'edit', 'racingSeriesID' => $racingSeriesID), 'adminRacingSeriesAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>');
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'racing-series', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'racing-series', 'action' => 'delete', 'racingSeriesID' => $racingSeriesID), 'adminRacingSeriesAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>');
        }

        return $this;
    }

    public function teamMenu($teamID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'team', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'team', 'action' => 'id', 'teamID' => $teamID), 'adminTeamID', true);
                array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>');

            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'team', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'team', 'action' => 'edit', 'teamID' => $teamID), 'adminTeamAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>');
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'team', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'team', 'action' => 'delete', 'teamID' => $teamID), 'adminTeamAction', true);
            array_push($this->_menuLinks, '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>');
        }

        return $this;
    }

    private function buildMenu()
    {
        if ($this->_blockMenu) {
            $this->_menuHtml = '<div class = "pull-right action-buttons block-config-menu">';
        } else {
            $this->_menuHtml = '<div class = "pull-right action-buttons">';
        }

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

    public function render()
    {

        if (count($this->_menuLinks) > 0) {
            $this->buildMenu();

            return $this->_menuHtml;
        } else {
            return $this->_emptyMenu;
        }
    }

    public function __toString()
    {
        return $this->render();
    }

}
