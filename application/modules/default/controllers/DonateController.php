<?php

class DonateController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Помощь проекту'));
    }

    public function indexAction(){
        //Set breadcrumb for this page
        $this->view->breadcrumb();

        $this->messages->addInfo($this->view->translate('<p>Доброго времени суток, посетитель портала.</p>'
            .'<p>Наш портал не так давно вышел на арену SIM-Racing и уже смог завоевать симпатии многих людей, как самый современный и быстро-развивающийся проект. '
            .'Мы постоянно развиваемся и поднимаем портал на более высокий уровень, что влечет за собой все большие затраты, время и нужда в ответственных людях. '
            .'Вам выпола возможность "вложить" кирпичик в фундамент портала. '
            .'Не проходите мимо и помогите любимому SIM-порталу стать еще лучше, а мы в свою очередь обещаем еще большую отдачу в разработке сайта, а так же еще больше интересных событий и гонок.</p>'
            .'<p>Спасибо, что вы с нами!</p>'));
        // Set head and page titles
        $this->view->pageTitle($this->view->translate('Помощь проекту'));
    }
}