<?php

  session_start();
  
  ini_set("memory_limit","64M");	
  
	define('APPLICATION_PATH','/application/');
	define('DATA_PATH','data/');
	define('TEMP_PATH','tmp/');
	define('RELATIVE_PATH',$_SERVER['DOCUMENT_ROOT'].APPLICATION_PATH);	

  if (isset($_SESSION['GUID']) && isset($_SESSION['DATETIME'])) {
		$uploaddir = RELATIVE_PATH.DATA_PATH;
		$extension = '';

		// Convert mime type to extension
	  switch ($_FILES['userfile']['type']) {
			case 'image/jpeg': 
     	  $extension='jpg';
			break;
			case 'image/png': 
			  $extension='png';
			break;
			case 'image/gif': 
			  $extension='gif';
			break;
		}

    // This time upload the file under it's guid
    // While processing the post before generating the actual pdf 
    // the image will be moved to the surname name birthdate pattern
		$basefile = sprintf('%s.jpg',$_SESSION['GUID']);

		$uploadfile = $uploaddir . $basefile;
		$tmpname = tempnam(RELATIVE_PATH.TEMP_PATH,'uissfoto');
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $tmpname)) {
			// resize the incoming file to 35x45mm
			
			//Waar komt het bestand vandaan?
			$bronvanhetbestand = $tmpname;
			
			//De hoogte en breedte ophalen van het plaatje
			$dimensions = getimagesize($bronvanhetbestand); 
			
			//Dit is de breedte die alle plaatjes krijgen
			$doelbreedte = 132;
      $doelhoogte = 170;
			
			//Hoogte en breedte toekennnen aan nieuwe variabelen
			$bronbreedte = $dimensions[0]; 
			$bronhoogte  = $dimensions[1];
			
			//De nieuwe hoogte berekenen aan de gegevens van het oude plaatje en de doel breedte
			$hoogte = ($bronhoogte * $doelbreedte) / $bronbreedte;
      
      if ($hoogte>$doelhoogte) {
        $doelbreedte = round(($bronbreedte * $doelhoogte) / $bronhoogte,0);        
      } else {
        //De hoogte, als het nodig is, afronden
        $doelhoogte = round($hoogte, 0);
      }

			
			$image=null;
			
			switch($extension) {
			  case 'gif': 
					$image = imagecreatefromgif($bronvanhetbestand);
				break;
			  case 'png': 
					$image = imagecreatefrompng($bronvanhetbestand);
				break;
			  case 'jpg': 
					$image = imagecreatefromjpeg($bronvanhetbestand);
				break;
			}
			
			//een nieuw klein plaatje maken met de gewenste grootte
			$destination = imagecreatetruecolor($doelbreedte, $doelhoogte);
			
			//Het nieuwe plaatje vullen met verkleinde plaatje
			imagecopyresampled($destination, $image, 0, 0, 0, 0, $doelbreedte, $doelhoogte, $bronbreedte, $bronhoogte);
			
			//Het plaatje weergeven
			imagesavealpha ( $destination, TRUE);
			imagejpeg($destination,RELATIVE_PATH.DATA_PATH.$basefile);

			//Het bronplaatje verwijderen
			imagedestroy($image);
			
			//Het doelplaatje verwijderen
			imagedestroy($destination);			
			
			// verwijder het tijdelijke bestand 
			unlink ($tmpname);
			
			echo sprintf('<img src="%s%s/%s">',APPLICATION_PATH,DATA_PATH,$basefile);
			
			
		} else {
			echo '<p>File could not be uploaded '.$basefile.' '.$_FILES['userfile']['error'].' with temporary name '.$tmpname.' document root '.RELATIVE_PATH.'</p>';
		}
	} else {
  	echo '<p>No session found</p>';
	}
	
	require_once(RELATIVE_PATH.'classes/class.utils.php');
	
?>