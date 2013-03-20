<?php

class App_View_Helper_ConfigureBlockItemMenu extends Zend_View_Helper_Abstract {

    private $_menu_html;
    private $_menu_empty;
    private $_links = array();

    public function ConfigureBlockItemMenu() {
        $this->_menu_empty = "";

        $this->_menu_html = "<div class=\"congigure_block_item_links\">";
        $this->_menu_html .= "<a class=\"dropdown-toggle contextual-links-trigger\" href=\"#\" data-toggle=\"dropdown\">";
        $this->_menu_html .= "<i class=\"icon-cog icon-black\"></i>";
        $this->_menu_html .= "<b class=\"icon-chevron-down\"></b>";
        $this->_menu_html .= "</a>";
        $this->_menu_html .= "<ul class=\"dropdown-menu\">";

        return $this;
    }

    public function championship_team_menu($championship_id, $team_id) {
        if ($this->view->checkUserAccess('championship/editteam')) {
            $link = $this->view->url(array('controller' => 'championship', 'action' => 'editteam', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeam', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Редактировать')}</a>");
        }

        if ($this->view->checkUserAccess('championship/editteam')) {
            $link = $this->view->url(array('controller' => 'championship', 'action' => 'adddriver', 'championship_id' => $championship_id, 'team_id' => $team_id), 'championshipTeamDriver', true);
            array_push($this->_links, "<a href=\"$link\">{$this->view->translate('Добавить гонщика')}</a>");
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