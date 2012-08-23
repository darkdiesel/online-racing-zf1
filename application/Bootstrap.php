<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initView()
    {
        // ������������� ����
        $view = new Zend_View();
        $view->doctype('XHTML1_STRICT');
        $view->headTitle('Online-Racing');
        $view->headLink()->appendStylesheet($view->baseUrl("css/style.css"));
        $view->headScript()->appendFile($view->baseUrl("scripts/jquery-1.8.0_min.js"));

        // ���������� ���� � ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        $viewRenderer->setView($view);
 
        // ��� �����������, ����� �������, �� ����� ���� �������� �����������
        return $view;
    }

}