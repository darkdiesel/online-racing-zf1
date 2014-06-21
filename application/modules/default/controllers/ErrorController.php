<?php

class ErrorController extends App_Controller_LoaderController {

	public function init() {
		parent::init();

		$this->view->headTitle($this->view->translate('Ошибка'));
		$this->view->pageTitle($this->view->translate('Ошибка'));
	}

	public function errorAction() {
        // set layout without sidebar
        Zend_Registry::set('default_layout_sidebar', 'no-sidebar');

		$errors = $this->_getParam('error_handler');

		if (!$errors || !$errors instanceof ArrayObject) {
			switch ($errors['type']) {
				case "access_denied":
					$this->messages->addError($this->view->translate('Доступ запрещен!'));
					break;
				default:
					$this->messages->addError($this->view->translate('You have reached the error page!'));
					break;
			}
			return;
		}

		//$requets = $this->getRequest();
		//echo $requets->getParam('message');

		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				// 404 error -- controller or action not found
				$this->getResponse()->setHttpResponseCode(404);
				$priority = Zend_Log::NOTICE;
				$this->messages->addError($this->view->translate('Ошибка 404. Страница не найдена'));
				$this->_helper->layout->setLayout( 'layout-error-404' );
				break;
			default:
				// application error
				$this->getResponse()->setHttpResponseCode(500);
				$priority = Zend_Log::CRIT;
				$this->messages->addError($this->view->translate('Ошибка 500. Ошибка приложения'));
				break;
		}

		// Log exception, if logger available
		if ($log = $this->getLog()) {
			/* $log->log($this->view->message, $priority, $errors->exception);
			  $log->log('Request Parameters', $priority, $errors->request->getParams()); */
			$log->log($this->view->message, $priority);
			$log->log('Request params: ' . print_r($errors->request->getParams(), true), $priority);
			$log->log($errors->exception, $priority);
		}

		// conditionally display exceptions
		if ($this->getInvokeArg('displayExceptions') == true) {
			$this->view->exception = $errors->exception;
		}

		$this->view->request = $errors->request;
	}

	public function getLog() {
		//return Zend_Registry::get('logger');
		$bootstrap = $this->getInvokeArg('bootstrap');
		if (!$bootstrap->hasResource('Log')) {
			return false;
		}
		$log = $bootstrap->getResource('Log');
		return $log;
	}

}
