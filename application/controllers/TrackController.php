<?php

class TrackController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Трасса'));
    }

    public function idAction() {
        // action body
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Track_Add();

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $country_data = array();

                //receive and rename image_round file
                if ($form->getValue('image_round')) {
                    if ($form->image_round->receive()) {
                        $file = $form->image_round->getFileInfo();
                        $ext = pathinfo($file['image_round']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['image_round']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter($file['image_round']['destination'] . '/' . $file['image_round']['name']);

                        $country_data['url_image_round'] = '/img/data/flags/' . $newName;
                    }
                }

                //receive and rename image_glossy_wave file
                if ($form->getValue('image_glossy_wave')) {
                    if ($form->image_glossy_wave->receive()) {
                        $file = $form->image_glossy_wave->getFileInfo();
                        $ext = pathinfo($file['image_glossy_wave']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['image_glossy_wave']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter($file['image_glossy_wave']['destination'] . '/' . $file['image_glossy_wave']['name']);

                        $country_data['url_image_glossy_wave'] = '/img/data/flags/' . $newName;
                    }
                }

                $date = date('Y-m-d H:i:s');
                $country_data['native_name'] = $form->getValue('native_name');
                $country_data['english_name'] = $form->getValue('english_name');
                $country_data['abbreviation'] = $form->getValue('abbreviation');
                $country_data['date_create'] = $date;
                $country_data['date_edit'] = $date;

                $country = new Application_Model_DbTable_Country();
                $newCountry = $country->createRow($country_data);
                $newCountry->save();

                $this->redirect($this->view->url(array('controller' => 'country', 'action' => 'id', 'id' => $newCountry->id), 'country', true));
            }
        }

        $country = new Application_Model_DbTable_Country();
        $countries = $country->getCountriesName('ASC');

        foreach ($countries as $country):
            $form->country->addMultiOption($country->id, $country->native_name . " ({$country->english_name})");
        endforeach;

        $this->view->form = $form;
    }

}

