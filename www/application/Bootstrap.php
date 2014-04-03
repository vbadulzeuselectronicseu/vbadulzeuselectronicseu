<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function initDocType() {
        // Define a constant for the Doctype string on the template
        $this->bootstrap('View');
        $view = $this->getResource('View');
        $view->doctype('HTML5');
    }
    
}