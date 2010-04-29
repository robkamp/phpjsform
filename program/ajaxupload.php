<?php

  session_start();
  
  ini_set("memory_limit","64M");	

  define('APPLICATION_PATH','/application/');
  define('TEMP_PATH','tmp/');
  define('STORAGE_PATH','storage/');

  define('RELATIVE_PATH',$_SERVER['DOCUMENT_ROOT'].APPLICATION_PATH);	
  require_once(RELATIVE_PATH.'classes/class.utils.php');

  if (isset($_SESSION['GUID']) && isset($_SESSION['DATETIME'])) {
		$uploaddir = RELATIVE_PATH.STORAGE_PATH;

    // This time upload the file under it's guid
    // While processing the post before generating the actual pdf 
    // the image will be moved to the surname name birthdate pattern
		$basefile = sprintf('%s.jpg',$_SESSION['GUID']);

    // Dit is de breedte die alle plaatjes krijgen
    $doelbreedte = 132;
    $doelhoogte = 170;

		$uploadfile = $uploaddir . $basefile;
		$originalphoto = sprintf('%s%s_original.%s',$uploaddir,$_SESSION['GUID'],$extension);
    $_SESSION['OriginalPhoto'] = $originalphoto;
    $_SESSION['OriginalExtension'] = $_FILES['userfile']['extension'];
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $originalphoto)) {
			// resize the incoming file to 35x45mm
			
			// De hoogte en breedte ophalen van het plaatje
			$dimensions = getimagesize($originalphoto); 
      $mimeType = image_type_to_mime_type($dimensions[2]);
      
      // Convert mime type to extension
      switch ($mimeType) {
        case 'image/jpeg': 
          $extension='jpg';
        break;
        case 'image/png': 
          $extension='png';
        break;
        case 'image/gif': 
          $extension='gif';
        break;
        default: 
          $extension='unknown';
        break;
      }
      $_SESSION['MimeType'] = $mimeType;

      if ($extension!='unknown') {
        
        // Hoogte en breedte toekennnen aan nieuwe variabelen
        $bronbreedte = $dimensions[0]; // haal de breedte op van de foto
        $bronhoogte  = $dimensions[1]; // haal de hoogte op van de foto 
        
        $factor = min(($doelbreedte / $bronbreedte),($doelhoogte / $bronhoogte)); // bepaal de schalingsfactor voor de foto

        $doelhoogte = round($bronhoogte * $factor,0);
        $doelbreedte = round($bronbreedte * $factor,0); 
              
        $image=null;
        switch($extension) {
          case 'gif': 
            $image = imagecreatefromgif($originalphoto);
          break;
          case 'png': 
            $image = imagecreatefrompng($originalphoto);
          break;
          default:
          case 'jpg': 
            $image = imagecreatefromjpeg($originalphoto);
          break;        
        }


        if (!$image) {
          echo '<p>File could not be uploaded '.$basefile.' '.$_FILES['userfile']['error'].' with temporary name '.$originalphoto.' document root '.RELATIVE_PATH.'</p>';
        } else {
          //een nieuw klein plaatje maken met de gewenste grootte
          $destination = imagecreatetruecolor($doelbreedte, $doelhoogte);
          
          //Het nieuwe plaatje vullen met verkleinde plaatje
          imagecopyresampled($destination, $image, 0, 0, 0, 0, $doelbreedte, $doelhoogte, $bronbreedte, $bronhoogte);
          
          //Het plaatje weergeven
          imagesavealpha($destination,TRUE);
          imagejpeg($destination,RELATIVE_PATH.STORAGE_PATH.$basefile,100); // Save the image with 100% quality
          $_SESSION['Photo'] = RELATIVE_PATH.STORAGE_PATH.$basefile;

          //Het bronplaatje verwijderen
          imagedestroy($image);
          
          //Het doelplaatje verwijderen
          imagedestroy($destination);			
          
          // verwijder het tijdelijke bestand - tijdelijk uitgeschakeld ivm zwarte foto's
          // unlink ($originalphoto);
          
          echo sprintf('<img src="%s%s/%s">',APPLICATION_PATH,STORAGE_PATH,$basefile);			
        }
      } else {  
         echo sprintf('<p class="disclaimer">Please upload another photo. This photo has an incorrect mime-type: %s</p>',$mimeType);
      }
    } else {
      echo '<p>File could not be uploaded '.$basefile.' '.$_FILES['userfile']['error'].' with temporary name '.$originalphoto.' document root '.RELATIVE_PATH.'</p>';
    }
  } else {
    echo '<p>No session found</p>';
  }
	
?>