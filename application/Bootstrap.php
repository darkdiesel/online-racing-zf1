<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initView()
    {
        // ������������� ����
        $view = new Zend_View();

        $view->headTitle('Online-Racing');
        $view->headLink()->appendStylesheet($view->baseUrl("css/bootstrap.min.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/style.css"));
        $view->headLink()->appendStylesheet($view->baseUrl("css/admin_menu.css"));
        $view->headScript()->appendFile($view->baseUrl("js/jquery-1.8.0_min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/bootstrap.min.js"));
        $view->headScript()->appendFile($view->baseUrl("js/jquery.lavalamp.js"));
        $view->headScript()->appendFile($view->baseUrl("js/my_js.js"));

        // ���������� ���� � ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        $viewRenderer->setView($view);
 
        // ��� �����������, ����� �������, �� ����� ���� �������� �����������
        return $view;
    }

}