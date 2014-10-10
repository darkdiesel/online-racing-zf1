<?php

class Admin_TrackController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Трасса'));
    }

    // action for view track
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'trackID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'trackID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_Track t')
                ->leftJoin('t.Country c')
                ->where('t.ID = ?', $requestData->trackID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->trackData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->translate('Трасса ') . ' :: ' . $result[0]['Name']
                );

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемая трасса не найдена!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Трасса не найдена!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Трасса не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all tracks
    public function allAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'page' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'page' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $this->view->headTitle($this->view->translate('Все'));
            $this->view->pageTitle($this->view->translate('Лиги'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_Track t')
                ->leftJoin('t.Country c')
                ->orderBy('t.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $trackPaginator = new Zend_Paginator($adapter);
            // pager settings
            $trackPaginator->setItemCountPerPage("10");
            $trackPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $trackPaginator->setPageRange("5");

            if ($trackPaginator->count() == 0) {
                $this->view->trackData = false;
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->trackData = $trackPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить трассу'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_Track_Add();
        $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'add'), 'default', true));

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {

                $new_track_data = array();

                //receive and rename track_logo file
                if ($form->getValue('track_logo')) {
                    if ($form->track_scheme->receive()) {
                        $file = $form->track_logo->getFileInfo();
                        $ext = pathinfo($file['track_logo']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_track_logo' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['track_logo']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter($file['track_logo']['destination'] . '/' . $file['track_logo']['name']);

                        $new_track_data['url_track_logo'] = '/data-content/data-uploads/track/logos/' . $newName;
                    }
                }

                //receive and rename track_scheme file
                if ($form->getValue('track_scheme')) {
                    if ($form->track_scheme->receive()) {
                        $file = $form->track_scheme->getFileInfo();
                        $ext = pathinfo($file['track_scheme']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_track_scheme' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['track_scheme']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter($file['track_scheme']['destination'] . '/' . $file['track_scheme']['name']);

                        $new_track_data['url_track_scheme'] = '/data-content/data-uploads/track/schemes/' . $newName;
                    }
                }

                $date = date('Y-m-d H:i:s');
                $new_track_data['name'] = $form->getValue('name');
                $new_track_data['track_year'] = $form->getValue('track_year');
                $new_track_data['track_length'] = $form->getValue('track_length');
                $new_track_data['city_id'] = $form->getValue('city');
                $new_track_data['country_id'] = $form->getValue('country');
                $new_track_data['description'] = $form->getValue('description');
                $new_track_data['date_create'] = $date;
                $new_track_data['date_edit'] = $date;

                $new_track = $this->db->get('track')->createRow($new_track_data);
                $new_track->save();

                $track_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'track_id' => $new_track->id), 'adminTrackId', true);
                $this->redirect($track_id_url);
            } else {
                $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        // Fill list of countries
        $countries = $this->db->get('country')->getAll(FALSE, array('id', 'NativeName', 'EnglishName'), array('EnglishName' => 'ASC'));
        $form->country->addMultiOption("", "");
        foreach ($countries as $country):
            $form->country->addMultiOption($country->id, $country->EnglishName . " ({$country->NativeName})");
        endforeach;

        $this->view->form = $form;
    }

    public function editAction()
    {
        $request = $this->getRequest();
        $track_id = (int)$request->getParam('track_id');

        $track_data = $this->db->get('track')->getItem($track_id);

        if ($track_data) {
            // Set Page Title and Heade Title
            $this->view->headTitle($track_data->name);
            $this->view->pageTitle($track_data->name);

            $track_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'track_id' => $track_id), 'adminTrackId', true);

            // form
            $form = new Application_Form_Track_Edit();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'edit', 'track_id' => $track_id), 'adminTrackAction', true));
            $form->cancel->setAttrib('onClick', "location.href=\"{$track_id_url}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    //receive and rename track_logo file
                    if ($form->getValue('track_logo')) {
                        if ($form->track_logo->receive()) {
                            $file = $form->track_logo->getFileInfo();
                            $ext = pathinfo($file['track_logo']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_track_logo' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['track_logo']['destination'] . '/' . $newName, 'overwrite' => true));

                            $filterRename->filter($file['track_logo']['destination'] . '/' . $file['track_logo']['name']);

                            $new_track_data['url_track_logo'] = '/data-content/data-uploads/track/logos/' . $newName;
                        }
                    }

                    //receive and rename track_scheme file
                    if ($form->getValue('track_scheme')) {
                        if ($form->track_scheme->receive()) {
                            $file = $form->track_scheme->getFileInfo();
                            $ext = pathinfo($file['track_scheme']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_track_scheme' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['track_scheme']['destination'] . '/' . $newName, 'overwrite' => true));

                            $filterRename->filter($file['track_scheme']['destination'] . '/' . $file['track_scheme']['name']);

                            $new_track_data['url_track_scheme'] = '/data-content/data-uploads/track/schemes/' . $newName;
                        }
                    }

                    $new_track_data['name'] = $form->getValue('name');
                    $new_track_data['track_year'] = $form->getValue('track_year');
                    $new_track_data['track_length'] = $form->getValue('track_length');
                    $new_track_data['city_id'] = $form->getValue('city');
                    $new_track_data['country_id'] = $form->getValue('country');
                    $new_track_data['description'] = $form->getValue('description');
                    $new_track_data['date_edit'] = date('Y-m-d H:i:s');

                    $track_where = $this->db->get('track')->getAdapter()->quoteInto('id = ?', $track_id);
                    $this->db->get('track')->update($new_track_data, $track_where);

                    $this->redirect($track_id_url);
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            // Fill form's list of countries
            $countries = $this->db->get('country')->getAll(FALSE, array('id', 'NativeName', 'EnglishName'), array('EnglishName' => 'ASC'));
            $form->country->addMultiOption("", "");
            foreach ($countries as $country):
                $form->country->addMultiOption($country->id, $country->EnglishName . " ({$country->NativeName})");
            endforeach;

            // Fill form's fields
            $form->name->setvalue($track_data->name);
            $form->track_year->setvalue($track_data->track_year);
            $form->track_length->setvalue($track_data->track_length);
            $form->city->setvalue($track_data->city_id);
            $form->country->setvalue($track_data->country_id);
            $form->description->setvalue($track_data->description);

            $this->view->form = $form;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемая трасса не найдена!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Трасса не найдена!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Трасса не найдена!')}");
        }
    }

    // action for delete track
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));
        $this->view->pageTitle($this->view->translate('Удалить трассу'));

        $request = $this->getRequest();
        $track_id = (int)$request->getParam('track_id');

        $track_data = $this->db->get('track')->getItem($track_id);

        if ($track_data) {
            $this->view->headTitle($track_data->name);

            $this->messages->addWarning($this->view->translate('Вы действительно хотите удалить трассу') . '<strong>' . $track_data->name . '</strong> ?');

            $track_delete_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'delete', 'track_id' => $track_id), 'adminTrackAction', true);
            $track_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'track_id' => $track_id), 'adminTrackId', true);

            $form = new Application_Form_Track_Delete();
            $form->setAction($track_delete_url);
            $form->cancel->setAttrib('onClick', "location.href='{$track_id_url}'");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $track_where = $this->db->get('track')->getAdapter()->quoteInto('id = ?', $track_id);
                    $this->db->get('track')->delete($track_where);

                    $this->messages->clearMessages();
                    $this->messages->addSuccess($this->view->translate('Трасса <strong>"' . $track_data->name . '"</strong> успешно удалена'));

                    $track_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'all', 'page' => 1), 'adminTrackAll', true);
                    $this->redirect($track_all_url);
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->form = $form;
            $this->view->track = $track_data;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемая трасса не найдена!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Трасса не найдена!')}");
            $this->view->pageTitle($this->view->translate('Ошибка!'));
        }
    }

}
