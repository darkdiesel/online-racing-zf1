<?php

class App_View_Helper_BlockConfigMenu extends Zend_View_Helper_Abstract
{

    private $_menuHtml;
    private $_emptyMenu;
    private $_menuLinks;
    private $_menuHeader;
    private $_blockMenu;

    private $_view_icon;
    private $_add_icon;
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
        $this->_add_icon = '<i class="fa fa-plus"></i> ';
        $this->_edit_icon = '<i class="fa fa-pencil"></i> ';
        $this->_delete_icon = '<i class="fa fa-trash-o"></i> ';

        return $this;
    }

    public function postMenu($postID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'post', 'id')) {
                $link = $this->view->url(array('module' => 'default', 'controller' => 'post', 'action' => 'id', 'postID' => $postID), 'defaultPostID');
                $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_view_icon  . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post', 'edit')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'edit', 'postID' => $postID), 'adminPostAction');
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_edit_icon  . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post', 'delete')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'post', 'action' => 'delete', 'postID' => $postID), 'adminPostAction');
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_delete_icon  . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function championshipMenu($championshipID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'id')) {
                $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'id', 'championshipID' => $championshipID), 'defaultChampionshipID');
                $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_view_icon  . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'championship', 'edit')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'edit', 'championshipID' => $championshipID), 'adminChampionshipAction');
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_edit_icon  . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'delete')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'delete', 'championshipID' => $championshipID), 'adminChampionshipAction', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_delete_icon  . $this->view->translate('Удалить') . '</a>';
        }

//        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'team-add')) {
//            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team-add', 'championshipID' => $championshipID), 'adminChampionshipAction', true);
//            $this->_menuLinks[] =  "<a href=\"$link\">{$this->view->translate('Добавить команду')}</a>";
//        }
//
//        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'add')) {
//            $link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'add', 'championshipID' => $championshipID), 'adminChampionshipAction', true);
//            $this->_menuLinks[] =  "<a href=\"$link\">{$this->view->translate('Добавить гонку')}</a>";
//        }

        return $this;
    }

    public function championshipRaceMenu($league_id, $championship_id, $race_id, $track_id)
    {
        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->view->translate('Редактировать гонку') . '</a>';
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'delete', 'league_id' => $league_id, 'championship_id' => $championship_id, 'race_id' => $race_id), 'defaultChampionshipRaceIdAction', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->view->translate('Удалить гонку') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'track', 'edit')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'edit', 'track_id' => $track_id), 'adminTrackAction', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->view->translate('Редактировать трассу') . '</a>';
        }

        return $this;
    }

    public function championshipTeamMenu($league_id, $championship_id, $team_id)
    {
        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'team-edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'team-edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'defaultChampionshipTeam', true);
            $this->_menuLinks[] =  "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>";
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'driver-add')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-add', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id), 'defaultChampionshipTeam', true);
            $this->_menuLinks[] =  "<a href=\"$link\">{$this->view->translate('Добавить гонщика')}</a>";
        }

        return $this;
    }

    public function championshipTeamDriverMenu($league_id, $championship_id, $team_id, $user_id)
    {
        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'driver-edit')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-edit', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id, 'user_id' => $user_id), 'defaultChampionshipTeamDriver', true);
            $this->_menuLinks[] =  "<a href=\"$link\">{$this->view->translate('Редактировать гонщика')}</a>";
        }

        if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'championship', 'driver-delete')) {
            $link = $this->view->url(array('module' => 'default', 'controller' => 'championship', 'action' => 'driver-delete', 'league_id' => $league_id, 'championship_id' => $championship_id, 'team_id' => $team_id, 'user_id' => $user_id), 'defaultChampionshipTeamDriver', true);
            $this->_menuLinks[] =  "<a href=\"$link\">{$this->view->translate('Удалить гонщика')}</a>";
        }

        return $this;
    }

    public function leagueMenu($leagueID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'league', 'id')) {
                $link = $this->view->url(array('module' => 'default', 'controller' => 'league', 'action' => 'edit', 'leagueID' => $leagueID), 'defaultLeagueID', true);
                $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'league', 'edit')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'league', 'action' => 'edit', 'leagueID' => $leagueID), 'adminLeagueAction', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_edit_icon  . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'league', 'delete')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'league', 'action' => 'delete', 'leagueID' => $leagueID), 'adminLeagueAction', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_delete_icon  . $this->view->translate('Удалить') . '</a>';
        }

        $this->_menuLinks[] = 'divider';

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'championship', 'add')) {
            $link = $this->view->url(array('module' => 'admin', 'controller' => 'championship', 'action' => 'add'), 'default', true);
            $this->_menuLinks[] =  '<a href="' . $link . '">' . $this->_add_icon . $this->view->translate('Добавить чемпионат') . '</a>';
        }

        return $this;
    }

    public function countryMenu($countryID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'country', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'country', 'action' => 'id', 'countryID' => $countryID), 'adminCountryID', true);
                $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'country', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'country', 'action' => 'edit', 'countryID' => $countryID), 'adminCountryAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'country', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'country', 'action' => 'delete', 'countryID' => $countryID), 'adminCountryAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function contentTypeMenu($contentTypeID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'content-type', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'content-type', 'action' => 'id', 'contentTypeID' => $contentTypeID), 'adminContentTypeID', true);
                $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'content-type', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'content-type', 'action' => 'edit', 'contentTypeID' => $contentTypeID), 'adminContentTypeAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'content-type', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'content-type', 'action' => 'delete', 'contentTypeID' => $contentTypeID), 'adminContentTypeAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function postCategoryMenu($postCategoryID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post-category', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'post-category', 'action' => 'id', 'postCategoryID' => $postCategoryID), 'adminPostCategoryID', true);
                $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post-category', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'post-category', 'action' => 'edit', 'postCategoryID' => $postCategoryID), 'adminPostCategoryAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'post-category', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'post-category', 'action' => 'delete', 'postCategoryID' => $postCategoryID), 'adminPostCategoryAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function racingSeriesMenu($racingSeriesID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'racing-series', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'racing-series', 'action' => 'id', 'racingSeriesID' => $racingSeriesID), 'adminRacingSeriesID', true);
                $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'racing-series', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'racing-series', 'action' => 'edit', 'racingSeriesID' => $racingSeriesID), 'adminRacingSeriesAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'racing-series', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'racing-series', 'action' => 'delete', 'racingSeriesID' => $racingSeriesID), 'adminRacingSeriesAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function teamMenu($teamID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'team', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'team', 'action' => 'id', 'teamID' => $teamID), 'adminTeamID', true);
                $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';

            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'team', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'team', 'action' => 'edit', 'teamID' => $teamID), 'adminTeamAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'team', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'team', 'action' => 'delete', 'teamID' => $teamID), 'adminTeamAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function roleMenu($roleID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'role', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'role', 'action' => 'id', 'roleID' => $roleID), 'adminRoleID', true);
                $this->_menuLinks[] = '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'role', 'edit')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'role', 'action' => 'edit', 'roleID' => $roleID), 'adminRoleAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'role', 'delete')) {
            $url = $this->view->url(array('module' => 'default', 'controller' => 'role', 'action' => 'delete', 'roleID' => $roleID), 'adminRoleAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function trackMenu($trackID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'track', 'id')) {
                $url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'trackID' => $trackID), 'adminTrackID', true);
                $this->_menuLinks[] = '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'track', 'edit')) {
            $url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'edit', 'trackID' => $trackID), 'adminTrackAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'track', 'delete')) {
            $url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'delete', 'trackID' => $trackID), 'adminTrackAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function raceEventMenu($raceEventID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race-event', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'race-event', 'action' => 'id', 'raceEventID' => $raceEventID), 'defaultRaceEventID', true);
                $this->_menuLinks[] = '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'race-event', 'edit')) {
            $url = $this->view->url(array('module' => 'admin', 'controller' => 'race-event', 'action' => 'edit', 'raceEventID' => $raceEventID), 'adminRaceEventAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'race-event', 'delete')) {
            $url = $this->view->url(array('module' => 'admin', 'controller' => 'race-event', 'action' => 'delete', 'raceEventID' => $raceEventID), 'adminRaceEventAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
        }

        return $this;
    }

    public function raceMenu($raceID, $addIDurl = false)
    {
        if ($addIDurl) {
            if ($this->view->checkUserAccess('default' . Acl::RESOURCE_SEPARATOR . 'race', 'id')) {
                $url = $this->view->url(array('module' => 'default', 'controller' => 'race', 'action' => 'id', 'raceID' => $raceID), 'defaultRaceID', true);
                $this->_menuLinks[] = '<a href="' . $url . '">' . $this->_view_icon . $this->view->translate('Просмотр') . '</a>';
            }
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'race', 'edit')) {
            $url = $this->view->url(array('module' => 'admin', 'controller' => 'race', 'action' => 'edit', 'raceID' => $raceID), 'adminRaceAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_edit_icon . $this->view->translate('Редактировать') . '</a>';
        }

        if ($this->view->checkUserAccess('admin' . Acl::RESOURCE_SEPARATOR . 'race', 'delete')) {
            $url = $this->view->url(array('module' => 'admin', 'controller' => 'race', 'action' => 'delete', 'raceID' => $raceID), 'adminRaceAction', true);
            $this->_menuLinks[] =  '<a href="' . $url . '">' . $this->_delete_icon . $this->view->translate('Удалить') . '</a>';
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

            if ($link == 'divider'){
                $links .= '<li class="divider"></li>';
            } else {
                $links .= '<li>';
                $links .= $link;
                $links .= '</li>';
            }
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
