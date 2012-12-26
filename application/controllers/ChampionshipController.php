<?php

class ChampionshipController extends App_Controller_FirstBootController
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить соревнование'));
        
        $request = $this->getRequest();
        // form
        $form = new Application_Form_Championship_Add();
        
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                
            }
        }
        // add leagues
        $league = new Application_Model_DbTable_League();
        $leagues = $league->fetchAll();
        
        if (count($leagues) != 0) {
            foreach ($leagues as $league):
                $form->league->addMultiOption($league->id, $league->name);
            endforeach;
        } else {
            $this->view->errMessage .= $this->view->translate('Лиги не найдены').'<br />';
        }

        
        // add reglaments
        $article_type = new Application_Model_DbTable_ArticleType();
        $reglaments_id = $article_type->getId('reglament');
        
        if ($reglaments_id) {
            $article = new Application_Model_DbTable_Article();
            $articles = $article->getPublishArticleTitlesByType($reglaments_id, 'ASC');
            
            if ($articles){
                
            } else {
                $this->view->errMessage .= $this->view->translate('Регламенты на сайте не найдены. Добавьте регламент, чтобы создать чемпионат!').'<br />';
            }
        } else {
            $this->view->errMessage .= $this->view->translate('Тип reglament не создан. Создайте тип reglament и добавьте регламент, чтобы создать чемпионат!').'<br />';
        }
        
        $this->view->form = $form;
    }


}

