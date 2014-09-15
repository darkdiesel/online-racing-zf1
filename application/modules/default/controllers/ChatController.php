<?php

class ChatController extends App_Controller_LoaderController {

	public function indexAction() {
		$this->view->headTitle($this->view->translate('Чат'));
		$this->view->pageTitle($this->view->translate('Чат'));

		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->messages->addInfo("{$this->view->translate('Введите сообщение в поле ввода и нажмите "Отправить".')}");
		} else {
			$this->messages->addWarning("{$this->view->translate('Сообщения в чате могут оставлять только авторизованные пользователи.')}"
					. "<br/><a class=\"btn btn-warning\" href=\"{$this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), 'default', true)}\">{$this->view->translate('Авторизоваться')}</a>"
					. " {$this->view->translate('или')} "
					. "<a class=\"btn btn-danger\" href=\"{$this->view->url(array('module' => 'default', 'controller' => 'reg', 'action' => 'user'), 'default', true)}\">{$this->view->translate('Зарегистрироваться')}</a>"
			);
		}

		$this->view->ls_chat_block = false;
	}

	public function addmessageAction() {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->_helper->layout->disableLayout();

			/*
			 * $this->view->layout()->disableLayout();
			 * $this->_helper->viewRender->setNoRender(true);
			 */
			$request = $this->getRequest();

			if ($this->getRequest()->isPost()) {
				if (($request->getParam('ajax_action') == 'add_message') && ($request->getParam('message_text') != '')) {
					$message_text = htmlspecialchars((trim($request->getParam('message_text'))));

					$date = date('Y-m-d H:i:s');
					$user_chat_data = array(
						'message' => $message_text,
						'user_id' => Zend_Auth::getInstance()->getStorage()->read()->id,
						'date_create' => $date,
						'date_edit' => $date,
					);

					$user_chat = new Application_Model_DbTable_UserChat();
					$newUser_chat_msg = $user_chat->createRow($user_chat_data);
					$newUser_chat_msg->save();

					$data_str = array('Result' => TRUE);
					echo json_encode($data_str);
				}
			}
		}
	}

	public function getmessagesAction() {
		$this->_helper->layout->disableLayout();
		$request = $this->getRequest();

		if ($this->getRequest()->isPost()) {
			if ($request->getParam('ajax_action') == 'get_chat_messages') {

				$user_chat = new Application_Model_DbTable_UserChat();
				$chat_messages = $user_chat->fetchLastMsg($request->getParam('last_act'));

				$order = $request->getParam('last_act') % 2;
				($order == 0) ? $order = 'odd' : $order = 'even';
				if ($chat_messages) {
					$last_message_id = 0;

					$messages_html = '';
					$bbcode = Zend_Markup::factory('Bbcode');

					foreach ($chat_messages as $message):
						if ($message->id > $last_message_id) {
							$last_message_id = $message->id;
						}

						//construct message html code
						$messages_html .= '<div class="chat_message_box ' . $order . '">';
						($order == 'even') ? $order = 'odd' : $order = 'even';
						$messages_html .= "<div class=\"chat_mesage_header\">";

						$messages_html .= "<div class=\"chat_mesage_user_avatar\">";
						$messages_html .= "<a href=\"{$this->view->url(array('module' => 'default', 'controller' => 'user', 'action' => 'id', 'user_id' => $message->user_id), 'defaultUserID', true)}\" target=\"_blank\"><i class=\"fa fa-user\"></i></a>";
						$messages_html .= '</div>';

						$messages_html .= '<div class="chat_mesage_user_nickname">';
						$messages_html .= '<a href="javascript:void(' . "'Apply to'" . ')" class="nick" onClick="$(' . "'#chat #userChat #messageTextArea').val($('#chat #userChat #messageTextArea').val() + '[i]'+$(this).html()+'[/i], '); $('#chat #userChat #messageTextArea').focus()" . '">' . $message->user_login . '</a>';
						$messages_html .= "</div>";

						$messages_html .= '<div class="chat_mesage_date">' . $message->date_create . '</div>';
						$messages_html .= "</div>";

						$messages_html .= '<div class="chat_mesage_message">' . $bbcode->render($message->message) . '</div>';
						$messages_html .= "</div>";
					endforeach;

					$data_str = array('message_html' => $messages_html, 'last_act' => $last_message_id);
					echo json_encode($data_str);
				} else {
					$data_str = array('message_html' => 'null', 'last_act' => 'null');
					echo json_encode($data_str);
				}
			}
		}
	}

}
