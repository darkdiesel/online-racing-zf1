<?php
class App_View_Helper_ShowMessages extends Zend_View_Helper_Abstract {
	protected $messages = array ();
	public function showMessages($type = null) {
		$session = new Zend_Session_Namespace ( 'App_Messages' );
		if (isset ( $session->messages )) {
			$messages = $session->messages;
			unset ( $session->messages );
			if ($type && isset ( $messages [$type] )) {
				foreach ( $messages [$type] as $message ) {
					$this->messages [] = array (
							'type' => $type,
							'text' => $message 
					);
				}
			} else {
				foreach ( $messages as $type => $typeMessages ) {
					foreach ( $typeMessages as $message ) {
						$this->messages [] = array (
								'type' => $type,
								'text' => $message 
						);
					}
				}
			}
		}
		
		if (count ( $this->messages )) {
			echo '<ul class="messages">';
			foreach ( $this->messages as $message ) {
				switch ($message ['type']) {
					case 'error' :
						echo '<li class="error-msg">' . $message ['text'] . "</li>";
						break;
					case 'success' :
						echo '<li class="success-msg">' . $message ['text'] . "</li>";
						break;
					case 'notice' :
						echo '<li class="notice-msg">' . $message ['text'] . "</li>";
						break;
					default :
						echo '<li>' . $message ['text'] . "</li>";
						break;
				}
			}
			echo '</ul>';
		}
	}
}