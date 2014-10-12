<?php

class Admin_RacingSeriesController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->t('Гоночная серия'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view racing series
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'racingSeriesID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'racingSeriesID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_RacingSeries rs')
                ->where('rs.ID = ?', $requestData->racingSeriesID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->racingSeriesData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->t('Гоночная серия') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая гоночная серия не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Гоночная серия не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Гоночная серия не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all racing series
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
            $this->view->pageTitle($this->view->t('Гоночные серии'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_RacingSeries rs')
                ->orderBy('rs.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $racingSeriesPaginator = new Zend_Paginator($adapter);
            // pager settings
            $racingSeriesPaginator->setItemCountPerPage("10");
            $racingSeriesPaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $racingSeriesPaginator->setPageRange("5");

            if ($racingSeriesPaginator->count() == 0) {
                $this->view->racingSeriesData = false;
                $this->messages->addInfo($this->view->t('Запрашиваемый контент на сайте не найден!'));
            } else {
                $this->view->racingSeriesData = $racingSeriesPaginator;
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new racing series
    public function addAction()
    {
        $this->view->headTitle($this->view->t('Добавить'));
        $this->view->pageTitle($this->view->t('Добавить гоночнyю серию'));

        // form
        $racingSeriesAddForm = new Peshkov_Form_RacingSeries_Add();
        $this->view->racingSeriesAddForm = $racingSeriesAddForm;

        // test for valid input
        // if valid, populate model
        // assign default values for some fields
        // save to database
        if ($this->getRequest()->isPost()) {
            if ($racingSeriesAddForm->isValid($this->getRequest()->getPost())) {

                $date = date('Y-m-d H:i:s');

                $item = new Default_Model_RacingSeries();

                $item->fromArray($racingSeriesAddForm->getValues());
                $item->DateCreate = $date;
                $item->DateEdit = $date;

                $item->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'id',
                            'racingSeriesID' => $item->ID), 'adminRacingSeriesID'
                    )
                );

//                $this->_helper->getHelper('FlashMessenger')->addMessage('Your submission has been accepted as item #' . $id . '. A moderator will review it and, if approved, it will appear on the site within 48 hours.');
            } else {
                $this->messages->addError(
                    $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                );
            }
        }
    }

    // action for edit racing series
    public function editAction()
    {
        $this->view->headTitle($this->view->t('Редактировать'));
        $this->view->pageTitle($this->view->t('Редактировать гоночную серию'));

        // set filters and validators for GET input
        $filters = array(
            'racingSeriesID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'racingSeriesID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {

            $racingSeriesEditForm = new Peshkov_Form_RacingSeries_Edit();
            $this->view->racingSeriesEditForm = $racingSeriesEditForm;

            if ($this->getRequest()->isPost()) {
                if ($racingSeriesEditForm->isValid($this->getRequest()->getPost())) {
                    $formData = $racingSeriesEditForm->getValues();

                    $item = Doctrine_Core::getTable('Default_Model_RacingSeries')->find($requestData->racingSeriesID);

                    // set edit date
                    $formData['DateEdit'] = date('Y-m-d H:i:s');

                    $item->fromArray($formData);
                    $item->save();

//                $this->_helper->getHelper('FlashMessenger')->addMessage('The record was successfully updated.');

                    $adminRacingSeriesIDUrl = $this->view->url(
                        array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'id', 'racingSeriesID' => $requestData->racingSeriesID),
                        'adminRacingSeriesID'
                    );

                    $this->redirect($adminRacingSeriesIDUrl);
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
                    ->from('Default_Model_RacingSeries rs')
                    ->where('rs.ID = ?', $requestData->racingSeriesID);

                $result = $query->fetchArray();

                if (count($result) == 1) {
                    $this->view->racingSeriesData = $result[0];
                    $this->view->racingSeriesEditForm->populate($result[0]);
                } else {
//                    throw new Zend_Controller_Action_Exception('Page not found', 404);

                    $this->messages->addError($this->view->t('Запрашиваемая гоночная серия не найдена!'));

                    $this->view->headTitle($this->view->t('Ошибка!'));
                    $this->view->headTitle($this->view->t('Гоночная серия не найдена!'));

                    $this->view->pageTitle($this->view->t('Ошибка!'));
                    $this->view->pageTitle($this->view->t('Гоночная серия не найдена!'));
                }
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for delete racing series
    public function deleteAction()
    {
        $this->view->headTitle($this->view->t('Удалить'));
        $this->view->pageTitle($this->view->t('Удалить гоночную серию'));

        // set filters and validators for GET input
        $filters = array(
            'racingSeriesID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'racingSeriesID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_RacingSeries rs')
                ->where('rs.ID = ?', $requestData->racingSeriesID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                // Create racing-series delete form
                $racingSeriesDeleteForm = new Peshkov_Form_RacingSeries_Delete();

                $this->view->racingSeriesData = $result[0];
                $this->view->racingSeriesDeleteForm = $racingSeriesDeleteForm;

                $this->view->headTitle($result[0]['Name']);

                $this->messages->addWarning(
                    $this->view->t('Вы действительно хотите удалить гоночную серию')
                    . " <strong>" . $result[0]['Name'] . "</strong>?"
                );

                if ($this->getRequest()->isPost()) {
                    if ($racingSeriesDeleteForm->isValid($this->getRequest()->getPost())) {
                        $query = Doctrine_Query::create()
                            ->delete('Default_Model_RacingSeries rs')
                            ->whereIn('rs.ID', $requestData->racingSeriesID);

                        $result = $query->execute();

                        //$this->_helper->getHelper('FlashMessenger')->addMessage('The records were successfully deleted.');

                        $this->messages->clearMessages();
                        $this->messages->addSuccess(
                            $this->view->t("Гоночная серия <strong>" . $this->view->racingSeriesData['Name'] . "</strong> успешно удалена."
                            )
                        );

                        $adminRacingSeriesAllUrl = $this->view->url(
                            array('module' => 'admin', 'controller' => 'racing-series', 'action' => 'all', 'page' => 1),
                            'adminRacingSeriesAll'
                        );

                        $this->redirect($adminRacingSeriesAllUrl);
                    } else {
                        $this->messages->addError(
                            $this->view->t('Исправьте следующие ошибки для корректного завершения операции!')
                        );
                    }
                }

            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->t('Запрашиваемая гоночная серия не найдена!'));

                $this->view->headTitle($this->view->t('Ошибка!'));
                $this->view->headTitle($this->view->t('Гоночная серия не найдена!'));

                $this->view->pageTitle($this->view->t('Ошибка!'));
                $this->view->pageTitle($this->view->t('Гоночная серия не найдена!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

}
