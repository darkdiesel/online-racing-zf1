<?php

class SitemapController extends App_Controller_LoaderController {

    public function indexAction() {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo $this->view->navigation()->sitemap();
    }

}