<?php

class Application_Form_Element_Phone extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formPhone';   

    public function __construct($fieldName, $attributes = null) {      
        parent::__construct($fieldName, $attributes);
    }  
}
