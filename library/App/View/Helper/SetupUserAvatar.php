<?php

class App_View_Helper_SetupUserAvatar extends Zend_View_Helper_Abstract {

    function setupUserAvatar($user_id, $avatar_type) {
        switch ($avatar_type) {
            case '0':
                $avatar = $this->view->gravatar()
                        ->setImgSize(200)
                        ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                        ->setSecure(true)
                        ->setAttribs(array('class' => 'img-thumbnail img-responsive', 'title' => 'no avatar'));

                return $avatar;
                break;
            case '1':
                /* Avatar load */
                //return '<div class="no_image">Not implemented</div>';

                $user = new Application_Model_DbTable_User();
                $avatar = $user->getUserAvatarLoad($user_id);

                if ($avatar) {
                    return '<img class="img-thumbnail img-responsive" src="' . $avatar . '" title="user avatar">';
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-thumbnail img-responsive', 'title' => 'no avatar'));

                    return $avatar;
                }

                break;
            case '2':
                /* Avatar link */
                $user = new Application_Model_DbTable_User();
                $avatar = $user->getUserAvatarLink($user_id);

                if ($avatar) {
                    return '<img class="img-thumbnail img-responsive" src="' . $avatar . '" title="user avatar">';
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-thumbnail img-responsive', 'title' => 'no avatar'));

                    return $avatar;
                }

                break;
            case '3':
                /* Avatar gravatar email */
                $user = new Application_Model_DbTable_User();
                $avatar = $user->getUserAvatarGravatarEmail($user_id);

                if ($avatar) {
                    $avatar = $this->view->gravatar()
                            ->setEmail($avatar)
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-thumbnail img-responsive', 'title' => "User avatar"));
                    return $avatar;
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => 'img-thumbnail img-responsive', 'title' => 'no avatar'));

                    return $avatar;
                }

                break;
        }
    }

}