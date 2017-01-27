<?php

class Zend_View_Helper_FormSource extends Zend_View_Helper_FormElement
{     
    public function formSource($name, $value = null, $attribs = null)
    {
        $internet = $referred = $tradeshow = $other = $social = $checked = "";    
        $source_internet = $source_tradeshow = $source_refer=$source_social=$source_other = "";
     //  var_dump($_POST);
              
        if(isset($_POST['source']) && $_POST['source'] == 'Internet') { $source_internet = $_POST['source_internet']; $internet = 'checked'; };
        if(isset($_POST['source']) && $_POST['source'] == 'Tradeshow') { $source_tradeshow = $_POST['source_tradeshow'];   $tradeshow = "checked";};
        if(isset($_POST['source']) && $_POST['source'] == 'Social Media') { $source_social = $_POST['source_social'];   $social = "checked";};
        if(isset($_POST['source']) && $_POST['source'] == 'Referred') { $source_refer = $_POST['source_refer']; $referred = 'checked';};
        if(isset($_POST['source']) && $_POST['source'] == 'Other') { $source_other = $_POST['source_other']; $other = 'checked';};
              
        //$user = Zend_Auth::getInstance()->getIdentity();
        if (Zend_Auth::getInstance()->getIdentity()) {
            $this->_users = new Application_Model_User;
            $user = $this->_users->getUser(Zend_Auth::getInstance()->getIdentity()->id);  

           // var_dump($user);
                if($value == 'Internet') { 
                    $internet = "checked"; 
                    if (!$source_internet && !isset($_POST['source'])) {
                      $source_internet = $user['source_text'];
                    }
                    $source_tradeshow = $source_refer=$source_social=$source_other = "";
                }
                if($value == 'Referred') { 
                    $referred = "checked"; 
                     if (!$source_refer && !isset($_POST['source'])) {
                        $source_refer = $user['source_text'];
                     }
                    $source_internet = $source_tradeshow = $source_social=$source_other = "";
                }
                 if($value == 'Social Media') { 
                    $social = "checked"; 
                     if (!$source_social && !isset($_POST['source'])) {
                        $source_social = $user['source_text'];
                     }
                    $source_internet = $source_refer = $source_tradeshow = $source_other = "";
                }
                
                if($value == 'Tradeshow') { 
                    $tradeshow = "checked"; 
                    if (!$source_tradeshow && !isset($_POST['source'])) {
                        $source_tradeshow = $user['source_text'];
                    }
                    $source_internet = $source_refer=$source_other = $source_social="";
                }
                if($value == 'Other') { 
                    $other = "checked"; 
                     if (!$source_other && !isset($_POST['source'])) {
                        $source_other = $user['source_text'];    
                     }
                    $source_internet = $source_tradeshow = $source_refer=$source_social="";
                }
        } else {
          if (!isset($_POST['source'])) {
              $source_internet = $source_tradeshow = $source_refer= $source_social = $source_other = "";   
          }
        }
    
        
     return '
        <div style="font-weight:bold;">        
        <input type="radio" name='. $name.' value="Internet" '.$internet.' >Internet Search
        <input type="text" name='. $name.'_internet value="'.$source_internet.'" placeholder="Keywords?" style="margin-left:50px;margin-bottom:10px;"><br>
        <input type="radio" name='. $name.' value="Referred" '.$referred.'>Referred 
            <input type="text" name='. $name.'_refer value="'.$source_refer.'" placeholder="Who Referred you?" style="margin-left:96px;margin-bottom:10px;"><br>
        <input type="radio" name='. $name.' value="Social Media" '.$social.'>Social Media 
            <input type="text" name='. $name.'_social value="'.$source_social.'" placeholder="Which One?" style="margin-left:66px;margin-bottom:10px;"><br>
        <input type="radio" name='. $name.' value="Tradeshow" '.$tradeshow.' >Trade Show
            <input type="text" name='. $name.'_tradeshow value="'. $source_tradeshow.'" placeholder="Which Trade Show?" style="margin-left:75px;margin-bottom:10px;" ><br> 
        <input type="radio" name='. $name.' value="Other" '.$other.'>Other
            <input type="text" name='. $name.'_other value="'. $source_other.'" placeholder="Other" style="margin-left:118px;"><br> 
        </div>
		<span>for testing</span>
        ';
    
    }
}

