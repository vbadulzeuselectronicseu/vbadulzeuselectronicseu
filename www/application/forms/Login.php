<?php

class Application_Form_Login extends Zend_Form
{
    
    public function __construct($options = null)
    {
        parent::__construct($options);
        
        $this->setName('contact_us');
        $this->setMethod('post');
        $this->setAction('/index/login');
        $this->setAttrib('onsubmit', 'form(this);return false;');
        $username = new Zend_Form_Element_Text('username', array(
            'id'         =>'username',
            'type'       =>'text',
            'name'       =>'username',
            'class'      =>'username',
            'placeholder'=>'Username',
        ));
        $password = new Zend_Form_Element_Password('password', array(
            'id'         =>'password',
            'type'       =>'password',
            'name'       =>'password',
            'class'      =>'password',
            'placeholder'=>'Password',
        ));
        $submit = new Zend_Form_Element_Button('Login',array(
           'type' => 'submit',
           'value'=> 'Sign me in'
        ));
        $this->addElements( array( $username ,$password , $submit ) );
        $this->addElement(
            'hidden',
            'dummy',
            array(
                'required' => false,
                'ignore'   => true,
                'autoInsertNotEmptyValidator' => false,
                'decorators' => array(
                    array(
                        'HtmlTag', array(
                            'tag'   => 'div', 
                            'id'    =>'idError',
                            'class' => 'error',
                        )
                    ),
                )
            )
        );
        $this->dummy->clearValidators();
    }

}