<?php

class IndexController extends App_Controller_LoaderController
{

    public function indexAction()
    {
        // page title
        $this->view->headTitle($this->view->translate('SIM-Racing Портал'));

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

        // Get Last Publish Posts
        $query = Doctrine_Query::create()
            ->from('Default_Model_Post p')
            ->leftJoin('p.User u')
            ->leftJoin('p.ContentType ct')
            ->leftJoin('p.PostCategory pt')
            ->where('p.Publish = ?', 1)
            ->limit(10)
            ->orderBy('p.ID DESC');
        $result = $query->fetchArray();

        $this->view->postData = $result;

        // Gel Leagues
        $query = Doctrine_Query::create()
            ->from('Default_Model_League l')
            ->leftJoin('l.User u')
            ->orderBy('l.ID ASC');
        $result = $query->fetchArray();

        $this->view->leagueData = $result;

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
            ), "id, name, description, championship_id", array('race_date' => 'ASC')
        );

//		$cache = Zend_Registry::get('cache');
//		if (!$result = $cache->load('mydata')) {
//			echo 'caching the data…..';
//			$data = array('1', ' 2', ' 3');
//			$cache->save($data, 'mydata');
//		} else {
//
//			echo 'retrieving cache data…….';
//			Zend_Debug::dump($result);
//		}
    }

    public function sitemapAction()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRender->setNoRender(true);
        echo $this->view->navigation()->sitemap();
    }

}
