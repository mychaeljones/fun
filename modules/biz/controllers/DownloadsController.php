<?php

class DownloadsController extends Zend_Controller_Action {

   public function init() {
        /*if (!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
           $this->_helper->redirector('login', 'user');
        }
        $this->m_user = new Application_Model_User;        
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_crms = new Application_Model_Crm;  */
        $this->m_crm = new Application_Model_Crm;
        $this->m_user = new Application_Model_User; 
        $this->m_file = new Application_Model_File; 
         
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        $this->_downloadsDir = 'data/downloads/';
        
    }

    public function indexAction() {
    
    }
    
    public function fileListsAction() {                
        if ($this->getParam('cat') == '') {
            $this->_helper->redirector('index', 'downloads'); 
        }
       
        $this->view->files = $this->m_user->getUserFiles($this->_auth->id, $this->getParam('cat'));  
        $this->view->dir = '/biz/data/downloads/';
    }
    
    public function uploadAction()
    {
      $this->view->categories = $this->m_crm->fileCategories();
        
      if ($this->getRequest()->isPost()) {
          
         // var_dump($_POST);
         // $dest_dir = 'data/uploads/';
          $ImageType = $_FILES['ImageFile']['type'];
       
        
         $upload = new Zend_File_Transfer_Adapter_Http();
			$upload->setDestination($this->_downloadsDir)
						 ->addValidator('Count', false, 1)
			//			 ->addValidator('Size', false, 1048576)
						 ->addValidator('Extension', false, 'jpg,png,gif,pdf,docx,xlsx,doc,xls');
			$files = $upload->getFileInfo();
                    //    var_dump($files);
                        
			try {
				// upload received file(s)
				$upload->receive();
			} 
			catch (Zend_File_Transfer_Exception $e) 
			{
				$e->getMessage();
				exit;
			}
			
			$mime_type = $upload->getMimeType();                        
			$fname = $upload->getFileName();
			$size = $upload->getFileSize();
			$file_ext = $this->getFileExtension($fname);
                        
                        $temp = time();
                        $newFileName = $temp;
                        
			$new_file = $this->_downloadsDir.$newFileName.'.'.$file_ext;
			
			$filterFileRename = new Zend_Filter_File_Rename(
				array(
					'target' => $new_file, 'overwrite' => true
			));
			
			$filterFileRename->filter($fname);
			
			if (file_exists($new_file)) {
				$request = $this->getRequest();
				$data['category'] = $request->getParam('category');
                                $data['userType'] = $request->getParam('userType');
                                $data['fileName'] = $newFileName.'.'.$file_ext;
                                $data['caption']  = $request->getParam('caption');     
                                $data['originalFileName'] = $files['ImageFile']['name'];
                                $this->m_crm->saveFiles($data);
			} else	{
				echo 'Unable to upload the file!';
			}
                
          
      if ($file_ext == 'png' || $file_ext == 'jpg' || $file_ext == 'gif' || $file_ext == 'jpeg') {
          ############ Edit settings ##############
	$ThumbSquareSize 		= 80; //Thumbnail will be 50X50
	$BigImageMaxSize 		= 500; //Image Maximum height or width
	$ThumbPrefix			= "thumb_"; //Normal thumb Prefix
	//$DestinationDirectory	= 'data/uploads/';
	$Quality 			= 90; //jpeg quality
	##########################################
	
	//check if this is an ajax request
		
	// Random number will be added after image name
	/*$RandomNumber 	= rand(0, 9999999999); 

	$ImageName 		= str_replace(' ','-',strtolower($_FILES['ImageFile']['name'])); //get image name
	$ImageSize 		= $_FILES['ImageFile']['size']; // get original image size
	$TempSrc	 	= $_FILES['ImageFile']['tmp_name']; // Temp name of image file stored in PHP tmp folder
	$ImageType	 	= $_FILES['ImageFile']['type']; //get file type, returns "image/png", image/jpeg, text/plain etc.
*/
	//Let's check allowed $ImageType, we use PHP SWITCH statement here
	switch(strtolower($file_ext))
	{
		case 'png':
			//Create a new image from file 
			$CreatedImage =  imagecreatefrompng($new_file);
			break;
		case 'gif':
			$CreatedImage =  imagecreatefromgif($new_file);
			break;			
		case 'jpeg':
		case 'jpg':
			$CreatedImage = imagecreatefromjpeg($new_file);
			break;
		default:
			die('Unsupported File!'); //output error and exit
	}
	
	//PHP getimagesize() function returns height/width from image file stored in PHP tmp folder.
	//Get first two values from image, width and height. 
	//list assign svalues to $CurWidth,$CurHeight
//	list($CurWidth,$CurHeight)=getimagesize($TempSrc);
	
	//Get file extension from Image name, this will be added after random name
	//$ImageExt = substr($ImageName, strrpos($ImageName, '.'));
  	//$ImageExt = str_replace('.','',$ImageExt);
	
	//remove extension from filename
	//$ImageName 		= preg_replace("/\\.[^.\\s]{3,4}$/", "", $ImageName); 
	
	//Construct a new name with random number and extension.
	//$NewImageName = $ImageName.'-'.$RandomNumber.'.'.$ImageExt;
	
	//set the Destination Image
	//$thumb_DestRandImageName 	= $DestinationDirectory.$ThumbPrefix.$NewImageName; //Thumbnail name with destination directory
	//$DestRandImageName 			= $DestinationDirectory.$NewImageName; // Image with destination directory
	
	//Resize image to Specified Size by calling resizeImage function.
        //list($CurWidth,$CurHeight)=getimagesize($TempSrc);
        //resizeImage($CurWidth,$CurHeight,$MaxSize,$DestFolder,$SrcImage,$Quality,$ImageType)
        list($CurWidth,$CurHeight)=getimagesize($new_file);
     
       // $DestRandImageName = $new_file;
       // $DestRandImageName = $dest_dir.'baby2.'.$file_ext;
        
        $thumb_DestRandImageName = $this->_downloadsDir.$ThumbPrefix. $data['fileName']; //Thumbnail name with destination directory
       // $CreatedImage = $new_file;
       // $ImageType = 'image/jpeg';
	//if(self::resizeImage($CurWidth,$CurHeight,$BigImageMaxSize,$DestRandImageName,$CreatedImage,$Quality,$ImageType))
	//{
		//Create a square Thumbnail right after, this time we are using cropImage() function
       
	//if(self::resizeImage($CurWidth,$CurHeight,$ThumbSquareSize,$thumb_DestRandImageName,$CreatedImage,$Quality,$ImageType)) {
        if(self::resizeImage($CurWidth,$CurHeight,$ThumbSquareSize,$thumb_DestRandImageName,$CreatedImage,$Quality,$ImageType)) {
			
		/*
		We have succesfully resized and created thumbnail image
		We can now output image to user's browser or store information in the database
		*/
		echo '<table width="100%" border="0" cellpadding="4" cellspacing="0">';
		echo '<tr>';
		echo '<td align="center"><img src="/biz/'.$thumb_DestRandImageName.'" alt="Thumbnail"></td>';
		echo '</tr>';
		echo '</table>';

		/*
		// Insert info into database table!
		mysql_query("INSERT INTO myImageTable (ImageName, ThumbName, ImgPath)
		VALUES ($DestRandImageName, $thumb_DestRandImageName, 'uploads/')");
		*/

	}else{
		die('gen thumb Error'); //output error
	}
        
                
	  
    

        /*  header('Pragma: no-cache');
          header('Cache-Control: private, no-cache');
          header('Content-Disposition: inline; filename="files.json"');
          header('X-Content-Type-Options: nosniff');
          header('Vary: Accept');
          echo json_encode($datas);*/
        }
       // if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
	   
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);	
        //}
      
        }
        
      /*if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
	   
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);	
        }*/
    }
    
    public function downloadAction() {        
         $file = $this->m_file->getFileById($this->_getParam('id'));
         $path = $this->_downloadsDir . DIRECTORY_SEPARATOR . $file['fileName'];      
         
         $fileSize = $this->m_file->get_real_size($path);
        
         if (file_exists($path)) {
            while (ob_get_level())
                ob_end_clean();
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($path));
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Cache-Control: private', false);
                header('Content-Length: ' . $fileSize);
                header('Connection: close');
                readfile($path);           
        } else {
            header("HTTP/1.1 404 Not Found");							
        }        
	$this->view->layout()->disableLayout();
	$this->_helper->viewRenderer->setNoRender(true);
    }  
    
  private function getFileExtension($filename) {
        $fext_tmp = explode('.', $filename);
        return $fext_tmp[(count($fext_tmp) - 1)];
    }

    
    // This function will proportionally resize image 
function resizeImage($CurWidth,$CurHeight,$MaxSize,$DestFolder,$SrcImage,$Quality,$ImageType)
{
	//Check Image size is not 0
	if($CurWidth <= 0 || $CurHeight <= 0) 
	{
		return false;
	}
	
	//Construct a proportional size of new image
	$ImageScale      	= min($MaxSize/$CurWidth, $MaxSize/$CurHeight); 
	$NewWidth  			= ceil($ImageScale*$CurWidth);
	$NewHeight 			= ceil($ImageScale*$CurHeight);
	$NewCanves 			= imagecreatetruecolor($NewWidth, $NewHeight);
	
	// Resize Image
	if(imagecopyresampled($NewCanves, $SrcImage,0, 0, 0, 0, $NewWidth, $NewHeight, $CurWidth, $CurHeight))
	{
		switch(strtolower($ImageType))
		{
			case 'image/png':
				imagepng($NewCanves,$DestFolder);
				break;
			case 'image/gif':
				imagegif($NewCanves,$DestFolder);
				break;			
			case 'image/jpeg':
			case 'image/pjpeg':
				imagejpeg($NewCanves,$DestFolder,$Quality);
				break;
			default:
				return false;
		}
	//Destroy image, frees memory	
	if(is_resource($NewCanves)) {imagedestroy($NewCanves);} 
	return true;
	}

}

    
    //This function corps image to create exact square images, no matter what its original size!
function cropImage($CurWidth,$CurHeight,$iSize,$DestFolder,$SrcImage,$Quality,$ImageType)
{	 
	//Check Image size is not 0
	if($CurWidth <= 0 || $CurHeight <= 0) 
	{
		return false;
	}
	
	//abeautifulsite.net has excellent article about "Cropping an Image to Make Square bit.ly/1gTwXW9
	if($CurWidth>$CurHeight)
	{
		$y_offset = 0;
		$x_offset = ($CurWidth - $CurHeight) / 2;
		$square_size 	= $CurWidth - ($x_offset * 2);
	}else{
		$x_offset = 0;
		$y_offset = ($CurHeight - $CurWidth) / 2;
		$square_size = $CurHeight - ($y_offset * 2);
	}
	
	$NewCanves 	= imagecreatetruecolor($iSize, $iSize);	
	if(imagecopyresampled($NewCanves, $SrcImage,0, 0, $x_offset, $y_offset, $iSize, $iSize, $square_size, $square_size))
	{
		switch(strtolower($ImageType))
		{
			case 'image/png':
				imagepng($NewCanves,$DestFolder);
				break;
			case 'image/gif':
				imagegif($NewCanves,$DestFolder);
				break;			
			case 'image/jpeg':
			case 'image/pjpeg':
				imagejpeg($NewCanves,$DestFolder,$Quality);
				break;
			default:
				return false;
		}
	//Destroy image, frees memory	
	if(is_resource($NewCanves)) {imagedestroy($NewCanves);} 
	return true;

	}
	  
}
  
}
