<?php

class Main_IndexController extends Zend_Controller_Action
{
    
    protected $_userModel;
    protected $session;
    protected $_myfiles;
    protected $url = 'DownloadFiles';

    public function init()
    {
        $this->_userModel = new Application_Model_User();
        $this->_myfiles = new Application_Model_Myfiles();
        $this->session = new Zend_Session_Namespace('Default');
        if( empty($this->session->user) ) {
            $this->_redirect("/");
        }
    }

    /**
    * indexAction
    *
    * @param  void
    * @return void
    */
    public function indexAction()
    {
        $file_description  = $this->getRequest()->getPost('description', null);
        $cool_search_words = $this->getRequest()->getPost('searchWords', null);
        $access_modifiers  = $this->getRequest()->getPost('access', null);
        $_files = $this->_myfiles->getFiles();
        
        // echo 123; 
        $br = function ($str) {
            $str = strip_tags($str,'<p><br/><b>');
            $str = preg_replace("/(\r\n){2,}/", "<br/><br/>", $str);
            $str = preg_replace("/(\r\n)/", "<br/>", $str);
            return $str;
        };

        $file_description  =  $br($file_description );
        $cool_search_words =  $br($cool_search_words);
        $access_modifiers  =  $br($access_modifiers);

        $uid                  = (int) $this->session->user['id'];
        $_url                 = $this->url;
        if($this->getRequest()->getMethod() == 'POST') {
            $upload = new Zend_File_Transfer_Adapter_Http();
            $upload ->addValidator('Extension', false, 'gif,png,jpg,jpeg,avi,mov,wmv,mpeg,mp4,flv,mpg')
                    ->addValidator('Size', false, array('min' => '1kB', 'max' => '20MB') )
                    ->setDestination($_url);
            $files = $upload->getFileInfo();
             $trigger = 0;
            $i=0;
            foreach ($files as $file => $info) {
                if($upload->isValid($file)){
                    $trigger = 1;
                    $ext = pathinfo($info['name']);
                    $_urlName = sha1('fille_').$uid.'_'.md5(date('Ymdhs')).'.'.$ext['extension'];
                    $upload->addFilter('Rename', array('target' => $_url .'/'. $_urlName, 'overwrite' => true)  );
                    $this->_myfiles->addFiles($uid , $file_description , $cool_search_words , $_urlName , $access_modifiers);
                    $upload->receive($file);
                    $i++;
                }
            } 
            // if($trigger == 0) { 
            //     if ( (!empty( $file_description)) || (!empty( $cool_search_words ))  ) {
            //         $this->_myfiles->addFiles($uid , $file_description , $cool_search_words ,'' , $access_modifiers);
            //     }
            // } 
            $this->_redirect("/main#myfiles");
        }
        $this->view->assign("view", array( 'count'=>count($_files), 'user'=>$this->session->user ) );
    
    }

    /**
    * contactAction
    *
    * @param  void
    * @return void
    */
    public function contactAction()
    {
        $this->_helper->layout->disableLayout();
    }
    /**
    * tvAction
    *
    * @param  void
    * @return void
    */
    public function tvAction()
    {
        $this->_helper->layout->disableLayout();
    }
    /**
    * photoAction
    *
    * @param  void
    * @return void
    */
    public function photoAction()
    {
        $this->_helper->layout->disableLayout();
    }
    /**
    * aboutAction
    *
    * @param  void
    * @return void
    */
    public function aboutAction()
    {
        $this->_helper->layout->disableLayout();
    }
    /**
    * myfilesAction()
    *
    * @param  void
    * @return void
    */
    public function myfilesAction()
    {
        $this->_helper->layout->disableLayout();
        $files = $this->_myfiles->getFiles();
        $this->view->assign("view", array('files' => $files, 'count'=>count($files) , 'user'=>$this->session) );
    }
    /**
    * accessstatusAction
    *
    * @param  void
    * @return void
    */
    public function accessstatusAction()
    {
        $this->_helper->layout->disableLayout();
        $id     = $this->getRequest()->getPost('id', null);
        $access     = $this->getRequest()->getPost('status', null);
        $json     = $this->getRequest()->getPost('json', null);
        if ( $json =='json' ) {
            $this->_myfiles->updateAccessModifiers($access , $id);
        }
    } 
    /**
    * mytextAction()
    *
    * @param  void
    * @return void
    */
    public function mytextAction()
    {    
        $this->_helper->layout->disableLayout(); 
        $this->view->assign("view", array('user'=>$this->session) );
    }
}

