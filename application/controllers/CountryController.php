<?php

class CountryController extends App_Controller_FirstBootController {

    public function init() {
        parent::init();
        $this->view->headTitle($this->view->translate('Страна'));
    }

    public function idAction() {
        $request = $this->getRequest();
        $country_id = (int) $request->getParam('country_id');

        $country = new Application_Model_DbTable_Country();
        $country_data = $country->getCountryData($country_id);

        if ($country_data) {
            $this->view->country = $country_data;
            $this->view->headTitle($country_data->native_name);
            $this->view->pageTitle("{$this->view->translate('Страна')} :: {$country_data->english_name} ({$country_data->native_name})");
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемая страна не существует!!')}");
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Страна не существует!')");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} :: $this->view->translate('Страна не существует!')");
        }
    }

    public function allAction() {
        $this->view->headTitle($this->view->translate('Все страны'));
        $this->view->pageTitle($this->view->translate('Все страны'));

        // pager settings
        $page_count_items = 10;
        $page_range = 5;
        $items_order = 'DESC';
        $page = $this->getRequest()->getParam('page');

        $country = new Application_Model_DbTable_Country();
        $paginator = $country->getCountriesPager($page_count_items, $page, $page_range, $items_order);
        
        if (count($paginator)){
            $this->view->paginator = $paginator;
        } else {
            $this->messageManager->addError("{$this->view->translate('Запрашиваемый контент на сайте не найден!')}");
        }
    }

    public function addAction() {
        $this->view->headTitle($this->view->translate('Добавить'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Country_Add();

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

                $this->redirect($this->view->url(array('controller' => 'country', 'action' => 'id', 'id' => $newCountry->id),'country', true));
            }
        }

        $this->view->form = $form;
    }

    public function editAction() {
        $this->view->headTitle($this->view->translate('Редактировать'));

        $request = $this->getRequest();
        $country_id = (int) $request->getParam('id');

        $country = new Application_Model_DbTable_Country();
        $country_data = $country->getCountryData($country_id);

        if ($country_data) {
            //create form and set some parameters
            $form = new Application_Form_Country_Edit();
            $form->setAction($this->view->url(array('controller' => 'contry', 'action' => 'edit', 'id' => $country_id), 'country', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'country', 'action' => 'id', 'id' => $country_id),'country', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {

                    $exist_country_native_name = $country->checkExistCountryNativeName($form->getValue('native_name'));
                    $exist_country_abbreviation = $country->checkExistCountryAbbreviation($form->getValue('abbreviation'));

                    if ($exist_country_native_name) {
                        if ($exist_country_native_name == $country_id) {
                            $update = TRUE;
                        } else {
                            $update = FALSE;
                        }
                    } else {
                        $update = TRUE;
                    }

                    if ($update) {
                        if ($exist_country_abbreviation) {
                            if ($exist_country_abbreviation == $country_id) {
                                $update = TRUE;
                            } else {
                                $update = FALSE;
                            }
                        } else {
                            $update = TRUE;
                        }
                    }

                    if ($update) {
                        $new_country_data = array();

                        //receive and rename first file 
                        if ($form->getValue('image_round')) {
                            if ($form->image_round->receive()) {
                                $file = $form->image_round->getFileInfo();
                                $ext = pathinfo($file['image_round']['name'], PATHINFO_EXTENSION);
                                $newName = Date('Y-m-d_H-i-s') . strtolower('_image_round' . '.' . $ext);

                                $filterRename = new Zend_Filter_File_Rename(array('target'
                                            => $file['image_round']['destination'] . '/' . $newName, 'overwrite' => true));

                                $filterRename->filter($file['image_round']['destination'] . '/' . $file['image_round']['name']);

                                $new_country_data['url_image_round'] = '/img/data/flags/' . $newName;

                                if ($new_country_data['url_image_round'] != $country_data['url_image_round']) {
                                    unlink(APPLICATION_PATH . '/../public_html' . $country_data['url_image_round']);
                                }
                            }
                        }

                        if ($form->getValue('image_glossy_wave')) {
                            if ($form->image_glossy_wave->receive()) {
                                $file = $form->image_glossy_wave->getFileInfo();
                                $ext = pathinfo($file['image_glossy_wave']['name'], PATHINFO_EXTENSION);
                                $newName = Date('Y-m-d_H-i-s') . strtolower('_image_glossy_wave' . '.' . $ext);

                                $filterRename = new Zend_Filter_File_Rename(array('target'
                                            => $file['image_glossy_wave']['destination'] . '/' . $newName, 'overwrite' => true));

                                $filterRename->filter($file['image_glossy_wave']['destination'] . '/' . $file['image_glossy_wave']['name']);

                                $new_country_data['url_image_glossy_wave'] = '/img/data/flags/' . $newName;

                                if ($new_country_data['url_image_glossy_wave'] != $country_data['url_image_glossy_wave']) {
                                    unlink(APPLICATION_PATH . '/../public_html' . $country_data['url_image_glossy_wave']);
                                }
                            }
                        }

                        $date = date('Y-m-d H:i:s');
                        $new_country_data['native_name'] = $form->getValue('native_name');
                        $new_country_data['english_name'] = $form->getValue('english_name');
                        $new_country_data['abbreviation'] = $form->getValue('abbreviation');
                        $new_country_data['date_edit'] = $date;

                        $country_where = $country->getAdapter()->quoteInto('id = ?', $country_id);
                        $country->update($new_country_data, $country_where);

                        $this->redirect($this->view->url(array('controller' => 'country', 'action' => 'id', 'id' => $country_id),'country', true));
                    } else {
                        $this->view->errMessage .= $this->view->translate('Неверное имя страны или аббревиация!') . ": {$form->getValue('native_name')} , {$form->getValue('abbreviation')}<br/>";
                        $this->view->errMessage .= $this->view->translate('Имя страны или аббревиатура уже существуют в базе данных!') . '<br/>';
                    }
                }
            }

            //head titles
            $this->view->headTitle($country_data->native_name . " ({$country_data->english_name})");

            //form values
            $form->native_name->setvalue($country_data->native_name);
            $form->english_name->setvalue($country_data->english_name);
            $form->abbreviation->setvalue($country_data->abbreviation);

            //get form for views
            $this->view->form = $form;
        } else {
            $this->view->errMessage .= $this->view->translate('Страна не найдена!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Страна не найдена!'));
        }
    }

    public function deleteAction() {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $country_id = (int) $request->getParam('id');

        $country = new Application_Model_DbTable_Country();
        $country_data = $country->getCountryData($country_id);

        if ($country_data) {
            //page title
            $this->view->headTitle($country_data->native_name . " ({$country_data->english_name})");

            //create delete form
            $form = new Application_Form_Country_Delete();
            $form->setAction($this->view->url(array('controller' => 'contry', 'action' => 'delete', 'id' => $country_id), 'country', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('controller' => 'country', 'action' => 'id', 'id' => $country_id),'country', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $country_where = $country->getAdapter()->quoteInto('id = ?', $country_id);
                    $country->delete($country_where);
                    $this->_helper->redirector('all', 'country');
                }
            }

            $this->view->country = $country_data;
            $this->view->form = $form;
        } else {
            $this->view->errMessage .= $this->view->translate('Страна не найдена!') . '<br/>';
            $this->view->headTitle($this->view->translate('Ошибка!'));
            $this->view->headTitle($this->view->translate('Страна не найдена!'));
        }
    }

}