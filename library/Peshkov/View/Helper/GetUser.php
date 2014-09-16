<?php

class Peshkov_View_Helper_GetUser extends Zend_View_Helper_Abstract
{

    public function getUser()
    {

        return $this;
    }

    public function getUserStatus($dateLastActivity)
    {
        $dateNow = new Zend_Date ();
        $dateLastActivity = new Zend_Date($dateLastActivity);

        if (($dateNow->toValue() - $dateLastActivity->toValue()) <= USER_ONLINE_PERIOD) {
            return USER_STATUS_ONLINE;
        } else {
            return USER_STATUS_OFFLINE;
        }
    }

    // TODO: Update method
    function getUserAvatar($userID, $avatarType, $img_class = '', $img_size = 200)
    {
        switch ($avatarType) {
            case '0':
                $avatar = $this->view->gravatar()
                    ->setImgSize($img_size)
                    ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                    ->setSecure(true)
                    ->setAttribs(array('class' => $img_class));
                return $avatar;
                break;
            case '1':
                $query = Doctrine_Query::create()
                    ->from('Default_Model_User u')
                    ->where('u.ID = ?', $userID);
                $userResult = $query->fetchArray();

                if ($userResult) {
                    return '<img class="' . $img_class . '" src="' . $userResult[0]['AvatarImageUrl'] . '">';
                } else {
                    $avatar = $this->view->gravatar()
                        ->setImgSize(200)
                        ->setDefaultImg(Zend_View_Helper_Gravatar::DEFAULT_MM)
                        ->setSecure(true)
                        ->setAttribs(array('class' => $img_class));

                    return $avatar;
                }

                break;

            case '3':
                /* Avatar gravatar email */
                $query = Doctrine_Query::create()
                    ->from('Default_Model_User u')
                    ->where('u.ID = ?', $userID);
                $userResult = $query->fetchArray();

                if ($userResult) {
                    $avatar = $this->view->gravatar()
                        ->setEmail($userResult[0]['AvatarGravatarEmail'])
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

    public function checkUserStatus($user)
    {
        if ($user['ActivationCode']) {
            return USER_STATUS_NOT_ACTIVATED;
        } else {
            if ($user['Status']) {
                return USER_STATUS_ENABLE;
            } else {
                return USER_STATUS_BLOCKED;
            }
        }
    }

    public function getUserIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif
        (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

}
