<?php

class Id_IndexController extends Zend_Controller_Action
{

    protected $_files;
    public function init()
    {
        $this->_files = new Application_Model_Myfiles();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $user = (int) $this->getRequest()->getParam('u');
        $post = (int) $this->getRequest()->getParam('p');
        if ( (!$user) || (!$post) ) {
            $this->_redirect("/");
        }
        if ( $data = $this->_files->getContent($user , $post) ){
            //var_dump($data); exit;
            $this->view->assign("view", array('data' => $data));
        } else {
            $this->_redirect("/");
        }
    }

}
