<?php

class Zend_View_Helper_FormPhone extends Zend_View_Helper_FormElement
{     
    public function formPhone($name, $value = null, $attribs = null)
    {
     return '(' .
                    $this->view->formText(
                        $name.'_areaCode',
                        substr($value, 0, 3),
                        array('maxlength' => 3, 'size' => 2)
                    ) .
               ') ' .
                    $this->view->formText(
                        $name.'_prefix',
                        substr($value, 3, 3),
                        array('maxlength' => 3, 'size' => 2)
                    ) .
               ' - ' .
                    $this->view->formText(
                        $name.'_suffix',
                        substr($value, 6, 4),
                        array('maxlength' => 4, 'size' => 3)
                    ) .
               ' ' .
               'Ext. ' .
                    $this->view->formText(
                        $name.'_ext',
                        substr($value, 10, strlen($value)),
                        array('maxlength' => 6, 'size' => 5)
             		);
    
    }
}

