<?php

class App_View_Helper_getUserAvatar extends Zend_View_Helper_Abstract {

    function getUserAvatar($user_id, $avatar_type, $img_class = 'img-thumbnail', $img_size = 200) {
        switch ($avatar_type) {
            case '0':
                $avatar = $this->view->gravatar()
                        ->setImgSize($img_size)
                        ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                        ->setSecure(true)
                        ->setAttribs(array('class' => $img_class));
                return $avatar;
                break;
            case '1':
                $user = new Application_Model_DbTable_User();
                $avatar = $user->getUserAvatarLoad($user_id);

                if ($avatar) {
                    return "<img class=\"{$img_class}\" src=\"{$avatar}\">";
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize(200)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => $img_class));

                    return $avatar;
                }

                break;
            case '2':
                /* Avatar link */
                $user = new Application_Model_DbTable_User();
                $avatar = $user->getUserAvatarLink($user_id);

                if ($avatar) {
                    return "<img class=\"{$img_class}\" src=\"{$avatar}\">";
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize($img_size)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => $img_class));

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
                            ->setImgSize($img_size)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => $img_class));
                    return $avatar;
                } else {
                    $avatar = $this->view->gravatar()
                            ->setImgSize($img_size)
                            ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                            ->setSecure(true)
                            ->setAttribs(array('class' => $img_class));

                    return $avatar;
                }
                break;
        }
    }

}