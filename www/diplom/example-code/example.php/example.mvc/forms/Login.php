<?php

class Form_Login extends Form_Base {

    public function init()
    {
        $email = new Zend_Form_Element_Text('email', array(
                    'required' => true,
                    'placeholder' => $this->translate('E-mail').' ...',
                    'validators' => array(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS |
                    Zend_Validate_Hostname::ALLOW_LOCAL))
                ));

        $password = new Zend_Form_Element_Password('password', array(
                    'required' => true,
                    'placeholder' => $this->translate('Password').' ...'
                ));

        $remember = new Zend_Form_Element_Checkbox('remember', array(
                    'label' => $this->translate('Remember me'),
                    'decorators' => array('viewHelper', 'Label')
                ));

        $referer = new Zend_Form_Element_Hidden('referer');
        if ($this->_request->getParam('referer')) {
            $referer->setValue(str_replace($this->_request->getBaseUrl(), '', $this->_request->getParam('referer')));
        } else {
            $referer->setValue(str_replace($this->_request->getBaseUrl(), '', $this->_request->getRequestUri()));
        }

        $this->addElements(array($email, $password, $remember, $referer));

        $this->setDecorators(
                array(
                    array('ViewScript', array('viewScript' => '_formLogin.phtml', 'form' => $this))
        ));

        //$this->setAction($this->getView()->url(array('module' => 'default', 'controller' => 'login'), 'default', true));
    }

    public function isValid($data)
    {
        if (!parent::isValid($data)
            || !$this->_visitor->authenticate($data['email'], $data['password'])) {
            $this->addError($this->translate('Invalid e-mail address or password.'));
            return false;
        }

        if(!empty($data['remember'])){ 
            Zend_Session::rememberMe(); // don't think!!! (c) Andrey
        }

        // set online user status
        $auth       = Zend_Auth::getInstance();
        $_visitorId = $auth->getStorage()->read()->id;
        $users      = new User_Model_DbTable_Users(); // todo: find a better place, not to query a second time
        $user       = $users->getRowById($_visitorId);
        $user->setOnline();

        return true;
    }

}