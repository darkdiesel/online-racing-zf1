<?php

class Admin_PostTypeController extends App_Controller_LoaderController
{

    public function init()
    {
        parent::init();
        $this->view->headTitle($this->view->translate('Тип статьи'));

        // set doctype for correctly displaying forms
        $this->view->doctype('XHTML1_STRICT');
    }

    // action for view post type
    public function idAction()
    {
        // set filters and validators for GET input
        $filters = array(
            'postTypeID' => array('HtmlEntities', 'StripTags', 'StringTrim')
        );
        $validators = array(
            'postTypeID' => array('NotEmpty', 'Int')
        );
        $requestData = new Zend_Filter_Input($filters, $validators);
        $requestData->setData($this->getRequest()->getParams());

        // test if input is valid
        // retrieve requested record
        // attach to view
        if ($requestData->isValid()) {
            $query = Doctrine_Query::create()
                ->from('Default_Model_PostType pt')
                ->where('pt.ID = ?', $requestData->postTypeID);
            $result = $query->fetchArray();

            if (count($result) == 1) {
                $this->view->postTypeData = $result[0];

                $this->view->headTitle($result[0]['Name']);
                $this->view->pageTitle(
                    $this->view->translate('Тип статьи') . ' :: ' . $result[0]['Name']
                );
            } else {
//                throw new Zend_Controller_Action_Exception('Page not found', 404);

                $this->messages->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));

                $this->view->headTitle($this->view->translate('Ошибка!'));
                $this->view->headTitle($this->view->translate('Тип статьи не найден!'));

                $this->view->pageTitle($this->view->translate('Ошибка!'));
                $this->view->pageTitle($this->view->translate('Тип статьи не найден!'));
            }
        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for view all post types
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
            $this->view->pageTitle($this->view->translate('Типы статей'));

            $query = Doctrine_Query::create()
                ->from('Default_Model_PostType pt')
                ->orderBy('pt.ID ASC');

            $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);

            $postTypePaginator = new Zend_Paginator($adapter);
            // pager settings
            $postTypePaginator->setItemCountPerPage("10");
            $postTypePaginator->setCurrentPageNumber($this->getRequest()->getParam('page'));
            $postTypePaginator->setPageRange("5");

            $this->view->postTypeData = $postTypePaginator;

            if ($postTypePaginator->count() == 0) {
                $this->messages->addInfo($this->view->translate('Запрашиваемый контент на сайте не найден!'));
            }

        } else {
            throw new Zend_Controller_Action_Exception('Invalid input');
        }
    }

    // action for add new post type
    public function addAction()
    {
        $this->view->headTitle($this->view->translate('Добавить'));
        $this->view->pageTitle($this->view->translate('Добавить тип статьи'));

        $request = $this->getRequest();
        // form
        $form = new Application_Form_PostType_Add();
        $form->setAction(
            $this->view->url(
                array('module' => 'admin', 'controller' => 'post-type', 'action' => 'add'), 'default', true
            )
        );

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($request->getPost())) {
                $date = date('Y-m-d H:i:s');
                $post_type_data = array(
                    'name' => strtolower($form->getValue('name')),
                    'description' => $form->getValue('description'),
                    'date_create' => $date,
                    'date_edit' => $date,
                );

                $new_post_type = $this->db->get('post_type')->createRow($post_type_data);
                $new_post_type->save();

                $this->redirect(
                    $this->view->url(
                        array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id',
                            'post_type_id' => $new_post_type->id), 'adminPostTypeID', true
                    )
                );
            } else {
                $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
            }
        }

        $this->view->form = $form;
    }

    // action for edit post type
    public function editAction()
    {
        $request = $this->getRequest();
        $post_type_id = $request->getParam('post_type_id');

        $this->view->headTitle($this->view->translate('Редактировать'));

        $post_type_data = $this->db->get('post_type')->getItem($post_type_id);

        if ($post_type_data) {
            // form
            $form = new Application_Form_ContentType_Edit();
            $form->setAction($this->view->url(
                array('module' => 'admin', 'controller' => 'post-type', 'action' => 'edit',
                    'post_type_id' => $post_type_id), 'adminPostTypeAction', true
            ));
            $form->cancel->setAttrib('onClick', "location.href=\"{$this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'all'), 'default', true)}\"");

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $new_post_type_data = array(
                        'name' => strtolower($form->getValue('name')),
                        'description' => $form->getValue('description'),
                        'date_edit' => date('Y-m-d H:i:s')
                    );

                    $post_type_where = $post_type->getAdapter()->quoteInto('id = ?', $post_type_id);
                    $post_type->update($new_post_type_data, $post_type_where);

                    $this->redirect($this->view->url(
                        array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id',
                            'post_type_id' => $post_type_id), 'adminPostTypeId', true
                    ));
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }
            $this->view->headTitle($post_type_data->name);
            $this->view->pageTitle("{$this->view->translate('Редактировать')} :: {$post_type_data->name}");

            $form->name->setvalue($post_type_data->name);
            $form->description->setvalue($post_type_data->description);

            $this->view->form = $form;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип статьи не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип статьи не найден!')}");
        }
    }

    // action for delete post type
    public function deleteAction()
    {
        $this->view->headTitle($this->view->translate('Удалить'));

        $request = $this->getRequest();
        $post_type_id = (int)$request->getParam('post_type_id');

        $post_type_data = $this->db->get('post_type')->getItem($post_type_id);

        if ($post_type_data) {
            $this->view->headTitle($post_type_data->name);
            $this->view->pageTitle("{$this->view->translate('Удалить тип статьи')} :: {$post_type_data->name}");

            $this->messages->addWarning("{$this->view->translate('Вы действительно хотите удалить тип статьи')} <strong>\"{$post_type_data->name}\"</strong> ?");

            $form = new Application_Form_PostType_Delete();
            $form->setAction($this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'delete', 'post_type_id' => $post_type_id), 'adminPostTypeAction', true));
            $form->cancel->setAttrib('onClick', 'location.href="' . $this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'id', 'post_type_id' => $post_type_id), 'adminPostTypeID', true) . '"');

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($request->getPost())) {
                    $post_type_where = $this->db->get('post_type')->getAdapter()->quoteInto('id = ?', $post_type_id);
                    $this->db->get('post_type')->delete($post_type_where);

                    $this->view->showMessages()->clearMessages();
                    $this->messages->addSuccess("{$this->view->translate("Тип статьи <strong>\"{$post_type_data->name}\"</strong> успешно удален")}");

                    $this->redirect($this->view->url(array('module' => 'admin', 'controller' => 'post-type', 'action' => 'all', 'page' => 1), 'adminPostTypeAll', true));
                } else {
                    $this->messages->addError($this->view->translate('Исправьте следующие ошибки для корректного завершения операции!'));
                }
            }

            $this->view->form = $form;
            $this->view->post_type = $post_type_data;
        } else {
            $this->messages->addError($this->view->translate('Запрашиваемый тип статьи не найден!'));
            $this->view->headTitle("{$this->view->translate('Ошибка!')} :: {$this->view->translate('Тип статьи не найден!')}");
            $this->view->pageTitle("{$this->view->translate('Ошибка!')} {$this->view->translate('Тип статьи не найден!')}");
        }
    }

}
