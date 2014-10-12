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
        $leagueResult = $query->fetchArray();

        $this->view->leagueData = $leagueResult;

        // Get Next Races
        $date = new Zend_Date();
        $dateStart = $date->toString('yyyy-MM-dd HH:mm:ss');
        $dateEnd = $date->add(7, Zend_Date::DAY)->toString('yyyy-MM-dd HH:mm:ss');

        $query = Doctrine_Query::create()
            ->from('Default_Model_RaceEvent re')
            ->leftJoin('re.Championship champ')
            ->where('re.DateStart >= ?', $dateStart)
            ->addWhere('re.DateStart <= ?', $dateEnd)
            ->orderBy('re.ID ASC');
        $raceEventResult = $query->fetchArray();

        $this->view->raceEventData = $raceEventResult;

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
