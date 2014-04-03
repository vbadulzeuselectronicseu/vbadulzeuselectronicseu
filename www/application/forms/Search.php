<?php

class Application_Form_Search extends Zend_Form
{

    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
        // Set the method for the display form to POST
        $this->setMethod('post');
        // Add an email element
        $this->addElement('text', 'email', array(
            'label'      => 'Your email address:',
            'required'   => true,
            'filters'    => array('StringTrim'),
            'validators' => array(
                'EmailAddress',
            )
        ));
        // Add a captcha
        $this->addElement('captcha', 'captcha', array(
            'label'      => 'Please enter the 5 letters displayed below:',
            'required'   => true,
            'captcha'    => array(
                'captcha' => 'Figlet',
                'wordLen' => 5,
                'timeout' => 300
            )
        ));
         // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'Sign Guestbook',
        ));
        $this->addElement('hash', 'csrf', array(
            'ignore' => true,
        ));
    }
}

