<?php

class Admin_TrackController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Трасса'));
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
                    $this->view->t('Трасса ') . ' :: ' . $result[0]['Name']
                );

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая трасса не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Трасса не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Трасса не найдена!'));
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
            $this->view->headTitle($this->view->t('Все'));
            $this->view->pageTitle($this->view->t('Трассы'));

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
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->trackData = $trackPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new track
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить трассу'));

        // form
        $trackAddForm = new Peshkov_Form_Track_Add();
        $this->view->trackAddForm = $trackAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($trackAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_Track();

                $formData = $trackAddForm->getValues();

                if (!$formData['Length']) {
                    $formData['Length'] = null;
                }

                if (!$formData['CityID']) {
                    $formData['CityID'] = null;
                }

                if (!$formData['Description']) {
                    $formData['Description'] = null;
                }

                $item->fromArray($formData);
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                //receive and rename track logo file
                if ($trackAddForm->getValue('LogoUrl')) {
                    if ($trackAddForm->LogoUrl->receive()) {
                        $file = $trackAddForm->LogoUrl->getFileInfo();
                        $ext = pathinfo($file['LogoUrl']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_track_logo' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['LogoUrl']['destination'] . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['LogoUrl']['destination'] . '/' . $file['LogoUrl']['name']
                        );

                        $item->LogoUrl = '/data-content/data-uploads/tracks/' . $newName;
                    }
                }

                //receive and rename track scheme file
                if ($trackAddForm->getValue('SchemeUrl')) {
                    if ($trackAddForm->SchemeUrl->receive()) {
                        $file = $trackAddForm->SchemeUrl->getFileInfo();
                        $ext = pathinfo($file['SchemeUrl']['name'], PATHINFO_EXTENSION);
                        $newName = Date('Y-m-d_H-i-s') . strtolower('_track_scheme' . '.' . $ext);

                        $filterRename = new Zend_Filter_File_Rename(array('target'
                        => $file['SchemeUrl']['destination']
                            . '/' . $newName, 'overwrite' => true));

                        $filterRename->filter(
                            $file['SchemeUrl']['destination'] . '/' . $file['SchemeUrl']['name']
                        );

                        $item->SchemeUrl = '/data-content/data-uploads/tracks/' . $newName;
                    }
                }

                $item->save();

                $this->messages->addSuccess(
                    $this->view->t("Трасса <strong>" . $item->Name . "</strong> успешно создана.")
                );

                $adminTrackIDUrl = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'id',
                        'trackID' => $item->ID), 'adminTrackID'
                );

                $this->redirect($adminTrackIDUrl);

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit track
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать трассу'));

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

            $trackEditForm = new Peshkov_Form_Track_Edit();
            $this->view->trackEditForm = $trackEditForm;

            if ($this->getRequest()->isPost()) {
                if ($trackEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $trackEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_Track')->find($requestData->trackID);

                    //receive and rename image logo file
                    if ($formData['LogoUrl']) {
                        if ($trackEditForm->LogoUrl->receive()) {
                            $file = $trackEditForm->LogoUrl->getFileInfo();
                            $ext = pathinfo($file['LogoUrl']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_track_logo' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['LogoUrl']['destination'] . '/'
                                . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['LogoUrl']['destination'] . '/' . $file['LogoUrl']['name']
                            );

                            $formData['LogoUrl'] = '/data-content/data-uploads/tracks/' . $newName;

                            if ($formData['LogoUrl'] != $item['LogoUrl']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['LogoUrl']);
                            }
                        }
                    } else {
                        unset($formData['LogoUrl']);
                    }

                    //receive and rename image scheme file
                    if ($formData['SchemeUrl']) {
                        if ($trackEditForm->SchemeUrl->receive()) {
                            $file = $trackEditForm->SchemeUrl->getFileInfo();
                            $ext = pathinfo($file['SchemeUrl']['name'], PATHINFO_EXTENSION);
                            $newName = Date('Y-m-d_H-i-s') . strtolower('_track_scheme' . '.' . $ext);

                            $filterRename = new Zend_Filter_File_Rename(array('target'
                            => $file['SchemeUrl']['destination'] . '/'
                                . $newName, 'overwrite' => true));

                            $filterRename->filter(
                                $file['SchemeUrl']['destination'] . '/' . $file['SchemeUrl']['name']
                            );

                            $formData['SchemeUrl'] = '/data-content/data-uploads/tracks/' . $newName;

                            if ($formData['SchemeUrl'] != $item['SchemeUrl']) {
                                unlink(APPLICATION_PATH . '/../public_html' . $item['SchemeUrl']);
                            }
                        }
                    } else {
                        unset($formData['SchemeUrl']);
                    }

                    if (!$formData['Length']) {
                        $formData['Length'] = null;
                    }

                    if (!$formData['CityID']) {
                        $formData['CityID'] = null;
                    }

                    if (!$formData['Description']) {
                        $formData['Description'] = null;
                    }

                    $item->fromArray($formData);

                    $item->DateEdit = date('Y-m-d H:i:s');

                    $item->save();

                    $this->messages->addSuccess(
                        $this->view->t("Трасса <strong>" . $item->Name . "</strong> успешно отредактирована.")
                    );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminTrackIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'trackID' => $requestData->trackID),
                        'adminTrackID'
                    );

                    $this->redirect($adminTrackIDUrl);
                } else {
                    $this->messages->addError(
                        $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                    );
                }
            } else {
                // if GET request
                // retrieve requested record
                // pre-populate form
                $query = Doctrine_Query::create()
                    ->from('Default_Model_Track t')
                    ->leftJoin('t.Country c')
                    ->where('t.ID = ?', $requestData->trackID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->trackData = $result[0];
                    $this->view->trackEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемая трасса не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Трасса не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Трасса не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete track
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить лигу'));

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
                // Create track delete form
                $trackDeleteForm = new Peshkov_Form_Track_Delete();

                $this->view->trackData = $result[0];
                $this->view->trackDeleteForm = $trackDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить трассу')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($trackDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_Track t')
                            ->whereIn('t.ID', $requestData->trackID);

                        $result = $query->execute();

                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->trackData['LogoUrl']);
                        unlink(APPLICATION_PATH . '/../public_html' . $this->view->trackData['SchemeUrl']);

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Трасса <strong>" . $this->view->trackData['Name'] . "</strong> успешно удалена."
                            )
                        );

                        $adminTrackAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'track', 'action' => 'all', 'page' => 1),
                            'adminTrackAll'
                        );

                        $this->redirect($adminTrackAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая трасса не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Трасса не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Трасса не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

//    // action for delete track
//    public function deleteAction()
//    {
//        $this->view->headTitle($this->view->t('Удалить'));
//        $this->view->pageTitle($this->view->t('Удалить трассу'));
//
//        $request = $this->getRequest();
//        $track_id = (int)$request->getParam('track_id');
//
//        $track_data = $this->db->get('track')->getItem($track_id);
//
//        if ($track_data) {
//            $this->view->headTitle($track_data->name);
//
//            $this->messages->addWarning($this->view->t('Вы действительно хотите удалить трассу') . '<strong>' . $track_data->name . '</strong> ?');
//
//            $track_delete_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'delete', 'track_id' => $track_id), 'adminTrackAction', true);
//            $track_id_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'id', 'track_id' => $track_id), 'adminTrackId', true);
//
//            $form = new Application_Form_Track_Delete();
//            $form->setAction($track_delete_url);
//            $form->cancel->setAttrib('onClick', "location.href='{$track_id_url}'");
//
//            if ($this->getRequest()->isPost()) {
//                if ($form->isValid($request->getPost())) {
//                    $track_where = $this->db->get('track')->getAdapter()->quoteInto('id = ?', $track_id);
//                    $this->db->get('track')->delete($track_where);
//
//                    $this->messages->clearMessages();
//                    $this->messages->addSuccess($this->view->t('Трасса <strong>"' . $track_data->name . '"</strong> успешно удалена'));
//
//                    $track_all_url = $this->view->url(array('module' => 'admin', 'controller' => 'track', 'action' => 'all', 'page' => 1), 'adminTrackAll', true);
//                    $this->redirect($track_all_url);
//                } else {
//                    $this->messages->addError($this->view->t('Исправьте следующие ошибки для корректного завершения операции!'));
//                }
//            }
//
//            $this->view->form = $form;
//            $this->view->track = $track_data;
//        } else {
//            $this->messages->addError($this->view->t('Запрашиваемая трасса не найдена!'));
//            $this->view->headTitle("{$this->view->t('Ошибка!')} :: {$this->view->t('Трасса не найдена!')}");
//            $this->view->pageTitle($this->view->t('Ошибка!'));
//        }
//    }

}
