<?php

class IndexController extends App_Controller_FirstBootController {

    public function indexAction() {
        // js and css for Skitter slider
        //$this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.easing.1.3.js"));
        //$this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.animate_colors.min.js"));
        $this->view->headScript()->appendFile($this->view->baseUrl("js/jquery.skitter.min.js"));
        $this->view->headLink()->appendStylesheet($this->view->baseUrl("css/skitter.styles.css"));

        // page title
        $this->view->headTitle($this->view->translate('Портал Онлай Скорости'));
        
        // get last publish articles
        $article = new Application_Model_DbTable_Article();
        $artiles_data = $article->get_last_publish_article(10, 'DESC');
        
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
        
        $this->view->last_articles = $artiles_data;
    }

}