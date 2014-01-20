<?php

class App_View_Helper_ShowMessages extends Zend_View_Helper_Abstract {

	protected $messages = array();
	protected $_error_messages = array();
	private $_success_messages = array();
	private $_warning_messages = array();
	private $_info_messages = array();
	private $_messages_html;

	public function showMessages($type = null) {
		$session = new Zend_Session_Namespace('App_Messages');
		if (isset($session->messages)) {
			$messages = $session->messages;
			unset($session->messages);
			if ($type && isset($messages [$type])) {
				foreach ($messages [$type] as $message) {
					$this->messages [] = array(
						'type' => $type,
						'text' => $message
					);
				}
			} else {
				foreach ($messages as $type => $typeMessages) {
					foreach ($typeMessages as $message) {
						$this->messages [] = array(
							'type' => $type,
							'text' => $message
						);
					}
				}
			}
		}

		if (count($this->messages)) {
			foreach ($this->messages as $message) {
				switch ($message ['type']) {
					case 'error' :
						$this->_error_messages[] = $message ['text'];
						break;
					case 'success' :
						$this->_success_messages[] = $message ['text'];
						break;
					case 'info' :
						$this->_info_messages[] = $message ['text'];
						break;
					case 'warning' :
						$this->_warning_messages[] = $message ['text'];
						break;
				}
			}
		}

		return $this;
	}

	public function __toString() {
		$this->_messages_html = '';
		$button = "<button type='button' class='close' data-dismiss='alert'>×</button>";
		$devider = "<hr class='message-inner-separator'>";

		if (count($this->_info_messages)) {
			$this->_messages_html .= "<div class='alert alert-block alert-info'>";
			$this->_messages_html .= $button;
			$this->_messages_html .= "<h4><i class='fa fa-info fa-lg'></i> {$this->view->translate('Информация!')}</h4>";
			$this->_messages_html .= $devider;

			foreach ($this->_info_messages as $message) {
				$this->_messages_html .= "<li>{$message}</li>";
			}

			$this->_messages_html .= "</div>";
		}

		if (count($this->_success_messages)) {
			$this->_messages_html .= "<div class'alert alert-block alert-success'>";
			$this->_messages_html .= $button;
			$this->_messages_html .= "<h4><i class='fa fa-check fa-lg'></i> {$this->view->translate('Успех!')}</h4>";
			$this->_messages_html .= $devider;

			foreach ($this->_success_messages as $message) {
				$this->_messages_html .= "<li>{$message}</li>";
			}

			$this->_messages_html .= "</div>";
		}

		if (count($this->_warning_messages)) {
			$this->_messages_html .= "<div class='alert alert-block alert-warning'>";
			$this->_messages_html .= $button;
			$this->_messages_html .= "<h4><i class='fa fa-exclamation-triangle fa-lg'></i> {$this->view->translate('Предупреждение!')}</h4>";
			$this->_messages_html .= $devider;

			foreach ($this->_warning_messages as $message) {
				$this->_messages_html .= "<li>{$message}</li>";
			}

			$this->_messages_html .= "</div>";
		}

		if (count($this->_error_messages)) {
			$this->_messages_html .= "<div class='alert alert-block alert-danger'>";
			$this->_messages_html .= $button;
			$this->_messages_html .= "<h4><i class='fa fa-exclamation-sign fa-lg'></i> {$this->view->translate('Ошибка!')}</h4>";
			$this->_messages_html .= $devider;

			foreach ($this->_error_messages as $message) {
				$this->_messages_html .= "<li>{$message}</li>";
			}

			$this->_messages_html .= "</div>";
		}

		return $this->_messages_html;
	}

	public function clearMessages() {
		$this->_error_messages = "";
		$this->_success_messages = "";
		$this->_info_messages = "";
		$this->_warning_messages = "";
	}

}
