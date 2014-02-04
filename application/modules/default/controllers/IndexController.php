<?php

class IndexController extends App_Controller_LoaderController {

	public function indexAction() {
		// js and css for Skitter slider
		//$this->view->headScript()->appendFile($this->view->baseUrl("library/skitter/jquery.skitter.min.js"));
		//$this->view->headLink()->appendStylesheet($this->view->baseUrl("library/skitter/css/skitter.styles.min.css"));
		// page title
		$this->view->headTitle($this->view->translate('SIM-Racing Портал'));

		// get last publish posts
		$post = new Application_Model_DbTable_Post();
		$artiles_data = $post->getLastPublishPost(10, 'DESC');

		// get 10 rss news
		$url = 'http://www.f1news.ru/export/news.xml';

		$xml = xml_parser_create();
		xml_parser_set_option($xml, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($xml, file_get_contents($url), $element, $index);
		xml_parser_free($xml);

		$count = 10;

		$this->view->rss_count = $count;
		$this->view->rss_index = $index;
		$this->view->rss_element = $element;

		$this->view->post_data = $artiles_data;
		// Gel Leagues
		$this->view->league_data = $this->db->get('league')->getAll(FALSE, array('id', 'name', 'url_logo', 'description'));

		// Get Next Races
		$date = new Zend_Date();
		$date_start = $date->toString('yyyy-MM-dd HH:mm:ss');
		$date_end = $date->add(7, Zend_Date::DAY)->toString('yyyy-MM-dd HH:mm:ss');

		$this->view->race_data = $this->db->get('championship_race')->getAll(
				array(
			'race_date' => array(
					array(
						'value' => $date_start,
						'sign' => ">"
					),
					array(
						'value' => $date_end,
						'sign' => "<",
						'condition' => "AND"
					)
				)
				), "id, name, description, championship_id", array('race_number' => 'ASC')
		);
	}

	public function sitemapAction() {
		$this->view->layout()->disableLayout();
		$this->_helper->viewRender->setNoRender(true);
		echo $this->view->navigation()->sitemap();
	}

}
