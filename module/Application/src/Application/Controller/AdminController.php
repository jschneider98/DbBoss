<?php
/**
 * User Administration Controller
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Form\UserForm;
use Application\Entity\User;

class AdminController extends DbossActionController
{
    public $require_login = true;
    public $require_connection = false;

    /**
     * 
     **/
    public function indexAction()
    {
        $template = array();

        if ($this->user->isLimited()) {
            // no access, redirect
        }

        $user_service = $this->getUserService();

        if ($this->user->isaBoss()) {
            $template['users'] = $user_service->findActiveUsers();
        } else {
            $template['users'] = $user_service->findInactiveUsers();
        }

        return $template;
    }

    /**
     * 
     **/
    public function editAction()
    {
        $user_id = (int) $this->params()->fromRoute('user_id', 0);
        $user = $this->getUserService()->findOrCreate($user_id);
        $user->password = null;

        $template = array('user' => $user);
        $form = new UserForm();
        $form->setup();
        $form->bind($user);

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setInputFilter($user->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $this->getUserService()->save($form->getData());

                return $this->redirect()->toRoute('admin');
            }
        }

        $template['form'] = $form;
        $template['user_id'] = $user_id;

        return $template;
    }

    /**
     * 
     **/
    protected function getUserService()
    {
        if (! $this->user_service) {
            $this->user_service = $this->getServiceLocator()->get('Application\Service\UserService');
        }
        return $this->user_service;
    }
}