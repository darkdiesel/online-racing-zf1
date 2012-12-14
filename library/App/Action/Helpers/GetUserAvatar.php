<?php

class Zend_Controller_Action_Helper_GetUserAvatar extends Zend_Controller_Action_Helper_Abstract {

    function get_image($user_id, $avatar_type) {
        switch ($avatar_type) {
            case '0':
                $avatar = $this->view->gravatar()
                        ->setImgSize(200)
                        ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                        ->setSecure(true)
                        ->setAttribs(array('class' => 'img-polaroid', 'title' => 'no avatar'));

                return $avatar;
                break;
            case '1':
                /* Avatar load */
                //return '<div class="no_image">Not implemented</div>';

                $user = new Application_Model_DbTable_User();
                $avatar = $user->get_user_avatar_load($user_id);
                
                if ($avatar) {
                    return '<img class="img-polaroid" src="' . $avatar . '" title="user avatar">';
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-polaroid', 'title' => 'no avatar'));

                    return $avatar;
                }
                
                break;
            case '2':
                /* Avatar link */
                $user = new Application_Model_DbTable_User();
                $avatar = $user->get_user_avatar_link($user_id);
                
                if ($avatar) {
                    return '<img class="img-polaroid" src="' . $avatar . '" title="user avatar">';
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-polaroid', 'title' => 'no avatar'));

                    return $avatar;
                }
                
                break;
            case '3':
                /* Avatar gravatar email */
                $user = new Application_Model_DbTable_User();
                $avatar = $user->get_user_avatar_gravatar_email($user_id);

                if ($avatar) {
                    $avatar = $this->view->gravatar()
                            ->setEmail($user_data->avatar_gravatar_email)
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-polaroid', 'title' => "User avatar"));
                    return $avatar;
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-polaroid', 'title' => 'no avatar'));

                    return $avatar;
                }

                break;
        }
    }

}