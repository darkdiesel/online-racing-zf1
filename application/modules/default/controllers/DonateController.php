<?php

class DonateController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Помощь проекту'));
    }

    public function indexAction(){
        //Set breadcrumb for this page
        $this->view->breadcrumb();

        $this->messages->addInfo($this->view->t('<p>Доброго времени суток, посетитель портала.</p>'
            .'<p>Наш портал не так давно вышел на арену SIM-Racing и уже смог завоевать симпатии многих людей, '
            .'как современный и быстро-развивающийся проект. Мы постоянно поднимаем портал на более высокий уровень, '
            .'что влечет за собой все большие затраты, и вам выпала возможность вложить кирпичик в фундамент развития проекта. '
            .'Не проходите мимо и помогите любимому SIM-порталу стать еще лучше, а мы в свою очередь обещаем еще большую отдачу в разработке сайта, '
            .'а также много интересных событий и гонок.</p>'
            .'<p>Спасибо, что вы с нами!</p>'));
        // Set head and page titles
        $this->view->pageTitle($this->view->t('Помощь проекту'));
    }
}