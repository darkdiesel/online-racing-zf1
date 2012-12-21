<?php

class CountryController extends App_Controller_FirstBootController {

    public function idAction() {
        
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить страну'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Country_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $file1 = $form->image_round->getFileInfo();
                $ext = pathinfo($file1['image_round']['name'], PATHINFO_EXTENSION);
                $newName1 = strtolower($form->abbreviation->getValue()) . '_image_round' . '.' . $ext;
                $form->image_round->addFilter('Rename', APPLICATION_PATH . '/../public_html/img/data/flags/' . $newName1);
                $form->image_round->receive();

                $file2 = $form->image_glossy_wave->getFileInfo();
                $ext = pathinfo($file2['image_glossy_wave']['name'], PATHINFO_EXTENSION);
                $newName2 = strtolower($form->abbreviation->getValue()) . '_image_glossy_wave' . '.' . $ext;
                $form->image_glossy_wave->addFilter('Rename', APPLICATION_PATH . '/../public_html/img/data/flags/' . $newName2);
                $form->image_glossy_wave->receive();

                $date = date('Y-m-d H:i:s');
                $country_data = array(
                    'name' => $form->getValue('name'),
                    'abbreviation' => strtoupper($form->getValue('abbreviation')),
                    'url_image_round' => '/img/data/flags/' . $newName1,
                    'url_image_glossy_wave' => '/img/data/flags/' . $newName2,
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $country = new Application_Model_DbTable_Country();
                $newCountry = $country->createRow($country_data);
                $newCountry->save();

                $this->redirect($this->view->baseURL('country/id/' . $newCountry->id));
            }
        }

        $this->view->form = $form;
    }

}