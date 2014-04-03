<?php

class IndexController extends Zend_Controller_Action
{
    protected $session;
    protected $_user;
    
    public function init()
    {
        $this->_user   = new Application_Model_User();
        $this->session = new Zend_Session_Namespace('Default');
    }

   /**
   * indexAction
   * 
   * @param void
   * @return void
   */
    public function indexAction()
    {
        $form = new Application_Form_Login();
        $this->view->form = $form;
        if( empty($this->session->user) ) {
            //$this->_redirect("/");
        } else {
            $this->_redirect("/main");
        }
        require_once 'Zend/Layout.php';
        Zend_Layout::startMvc();
    }

    /**
    * loginAction
    * 
    * @param  void
    * @return void
    */
    public function loginAction()
    {
        $username = $this->getRequest()->getPost('username', null);
        $password = $this->getRequest()->getPost('password', null);
        $this->session->user = $this->_user->login($username , $password);
        //var_dump($this->session->user);
        if( empty($this->session->user) ) {
            $this->_redirect("/");
        } else {
            $this->_redirect("/main");
        }
    }

    /**
    * logoutAction
    * 
    * @param  void
    * @return void
    */
    public function logoutAction()
    { 
        $this->_user->logout();
        $this->_redirect("/");
        exit();
    }
     /**
    * logoutAction
    * 
    * @param  void
    * @return void
    */
    public function registrationAction()
    {
        $this->_helper->layout->disableLayout();
        $mail = $this->getRequest()->getPost('inputMail', null);
        $this->_user->registration($mail); 
    }
    public function testAction()
    {
        var_dump( $this->getRequest()->getParam('id') );
        exit('exit');
    }

}

