<?php

require_once(RELATIVE_PATH.'classes/class.phpmailer.php');
require_once(RELATIVE_PATH.'classes/class.data.php');
require_once(RELATIVE_PATH.'classes/class.utils.php');
require_once(RELATIVE_PATH.'plugins/dompdf/dompdf_config.inc.php');

define('DATA_PATH','data/');
define('TEMP_PATH','tmp/');

class form {
  private $form;
  private $finished;
  private $content;
  private $renderPdf=false;
  private $data;
  private $keywords=array('checkbox','radioset','datepicker','fill-in','dropdown','fileupload','submit','location','debug','disclaimer');
  private $errors=array();
  private $replaceStack=array();
  private $countries;
  private $sessions;
  private $location;
  private $session;
	private $htmlmail;
  private $method;
	private $debug=false;
	private $emailSettings = array('from'=>'director@uiss.org');

  function form($form,$finished,$pdf,$htmlmailstudent,$htmlmaildirector) {
    if (file_exists($form) && file_exists($finished) && file_exists($pdf) && file_exists($htmlmailstudent)&& file_exists($htmlmaildirector)) {
      $this->form = $form;
      $this->pdf = $pdf;
      $this->finished = $finished;
      $this->filename = '';
			$this->htmlmailstudent = $htmlmailstudent;
			$this->htmlmaildirector = $htmlmaildirector;
      $this->location = sprintf('http://%s%s',$_SERVER['SERVER_NAME'],APPLICATION_PATH);
      $this->fluency1 = new dataArray('fluency1');
      $this->fluency2 = new dataArray('fluency2');
      $this->titles = new dataArray('titles');
      $this->countries = new dataArray('countries');
      $this->usstates = new dataArray('usstates');
      $this->nationalities = new dataArray('nationalities');
      $this->sessions = new dataArray('sessions');
      $this->afternoon1 = new dataArray('afternoon1');
      $this->afternoon2 = new dataArray('afternoon2');
      $this->morning = new dataArray('morning');

//			if ($_SERVER['HTTP_HOST']=='uiss.nl.eu.org') {
//				$this->emailSettings['from']='rob@kamp.nl.eu.org';
//				$this->debug = true;
//			}
			
    } else {
      die(sprintf("Form %s does not exist",$form));
    }
  }

  function parse ($method='get') {

		// In een class moet deze code in de class staan. 
		// Niet erbuiten, dan wordt er namelijk al output gedaan naar de client
		date_default_timezone_set('UTC');  

    
    
		switch ($method) {
			case 'post': {
				$this->data=array_merge($_REQUEST,$_SESSION);
				$content = file_get_contents($this->pdf);
        $this->filename = sprintf('%s%s_%s_%s_%s',DATA_PATH,$_SESSION['DATETIME'],$this->data['family_name'],$this->data['first_name'],$this->data['date_of_birth']);
				$this->renderPdf=true;
        // Now that we are rendering the PDF we should move 
        // the image from its temporary name to its final name
        rename (RELATIVE_PATH.DATA_PATH.$_SESSION['GUID'].'.jpg',RELATIVE_PATH.$this->filename.'.jpg');
				break;
			}
			case 'get': {
        // We just did a get 
        $_SESSION['GUID']=utils::uuid();
        $_SESSION['DATETIME']=date('YmdHi');
				$content = file_get_contents($this->form);
				$this->renderPdf=false;
        $this->data=array();
				break;
			}
    }
    
    if (isset($_REQUEST['debug'])&&$_REQUEST['debug']=='2wsxCDE') {
      $this->debug = true;
    }

    $tokens=preg_split('/{|}/',$content);

    foreach ($tokens as $key=>$token) {
      $keyword='';
      $name='';
      $value='';
			$default='';
			$label='';
      switch (count(split(" ",$token))) {
        case 0:
          throw new Exception('Cannot render token '.$token);
          break;
        case 1:
          $keyword = $token;
          break;
        case 2:
          list($keyword,$name) = split(" ",$token);
          break;
        default:
          list($keyword,$name,$value) = split(" ",$token,3);
          break;
      }
			
      if (in_array($keyword,$this->keywords)) {
        switch ($keyword) {
          case 'debug':
            $this->debuginfo($token);
            break;
          case 'disclaimer': 
            $this->disclaimer($token);
            break;
          case 'location':
            $this->location($token);
            break;
          case 'fileupload':
            $this->fileupload($token,$name);
            break;
          case 'checkbox':
	          list($keyword,$name,$value) = split(" ",$token,3);
            $this->checkbox($token,$name,$value);
            break;
          case 'radioset':
 						list($keyword,$name,$value,$label) = split(" ",$token,4);
            $this->radioset($token,$name,$value,$label);
            break;
          case 'datepicker':
            $this->datepicker($token,$name);
            break;
          case 'fill-in':
            $this->fillin($token,$name);
            break;
          case 'dropdown':
						switch((count(split(" ",$token)))) {
							case 0:
							case 1:
							case 2:
							  die($token);
							break;
							case 3:
								list($keyword,$name,$value) = split(" ",$token);
		            $this->dropdown($token,$name,$value);
							break;
							case 4:
								list($keyword,$name,$value,$default) = split(" ",$token);
		            $this->dropdown($token,$name,$value,$default);
							break;
							default: 
								list($keyword,$name,$value,$default,$label) = split(" ",$token,5);
		            $this->dropdown($token,$name,$value,$default,$label);
							break;
						}
            break;
        }
      }
    }

		// Render the content 
    foreach ($this->replaceStack as $search=>$replace) {
      $content=str_replace('{'.$search.'}',$replace,$content);
    }
		
		// Store the content on the session
		foreach ($this->data as $key=>$value) {
			$_SESSION[$key] = $value;
		}
		
    if ($this->renderPdf) {
      $dat = '';
      foreach ($this->data as $key=>$value) {
        $dat.=sprintf('%s=%s'.chr(10),$key,$value);
      };
      $dat.=sprintf('%s=%s'.chr(10),'location',$this->location);
      $dat.=sprintf('%s=%s'.chr(10),'filename',$this->filename);
      $dat.=sprintf('%s=%s'.chr(10),'HTTP_USER_AGENT',$_SERVER['HTTP_USER_AGENT']);
			// $dat =join(';',array_keys($this->data)).';location'.chr(10);
      // $dat.=join(';',$this->data).';'.$this->location;
      $file=sprintf('%s%s',RELATIVE_PATH,$this->filename);
      
			file_put_contents($file.'.dat',$dat);
      if ($this->debug) {
        file_put_contents(RELATIVE_PATH.DATA_PATH.$_SESSION['GUID'].'.html',$content);
      }
			
			$this->pdf_create($file.'.pdf',$content);
      $this->sendMailDirector($file.'.pdf',$file.'.dat',$file.'.jpg');
      $this->sendMailStudent($file.'.pdf');
			
      $content = file_get_contents($this->finished);
			foreach ($this->replaceStack as $search=>$replace) {
				$content=str_replace('{'.$search.'}',$replace,$content);
			}
			
      session_destroy();
    } 
    print $content;
  }

  function debuginfo($token) {
    if ($this->debug) { 
      $this->replaceStack[$token]=sprintf('<p class="disclaimer">debugging mode an extra html file will be generated in %s.html</p>',$_SESSION['GUID']);
    } else {
      $this->replaceStack[$token]='';
    }
  }

  function disclaimer($token) {
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
      $this->replaceStack[$token]='<p class="disclaimer">Please note: this online application form might not work with Internet Explorer 7 or lower.</p>';
    } else {
      $this->replaceStack[$token]='';
    }
  }
    
  
  function sendMailStudent($pdffile) {

		$content = file_get_contents($this->htmlmailstudent);
		foreach ($this->replaceStack as $search=>$replace) {
			$content=str_replace('{'.$search.'}',$replace,$content);
		}			

		$mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
		$mail->IsMail();
		try {
			$mail->CharSet = "UTF-8";
			$mail->IsHTML(true);
			$mail->AddAddress($this->data['e-mail']);
		  $mail->SetFrom($this->emailSettings['from']);
		  $mail->AddReplyTo($this->emailSettings['from']);
			$mail->Subject = 'Enrollment to UISS course (Student)';
			$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
			$mail->MsgHTML($content);
			$mail->AddAttachment($pdffile);      // attachment
			$mail->Send();
		} catch (phpmailerException $e) {
			die($e->errorMessage().' while sending an email to the student'); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
	}

  function sendMailDirector($pdffile,$datfile,$photo) {
		$content = file_get_contents($this->htmlmaildirector);
		foreach ($this->replaceStack as $search=>$replace) {
			$content=str_replace('{'.$search.'}',$replace,$content);
		}			

		$mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
		$mail->IsMail();
		try {
			$mail->CharSet = "UTF-8";
			$mail->IsHTML(true);
			$mail->AddAddress($this->emailSettings['from']);
			$mail->Subject = 'Enrollment to UISS course (Director)';
			$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
		  $mail->SetFrom($this->emailSettings['from']);
		  $mail->AddReplyTo($this->data['e-mail']);
			$mail->MsgHTML($content);
      $mail->AddAttachment($photo);      // attachment
			$mail->AddAttachment($pdffile);    // attachment
			$mail->AddAttachment($datfile);     // attachment
			$mail->Send();
		} catch (phpmailerException $e) {
			die($e->errorMessage().' while sending an email to the director'); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
  }

  function fileupload($token,$name) {
    if ($this->renderPdf) {
			$photo=sprintf('%s%s.jpg',$this->location,$this->filename);
			list($width,$height,$type,$flag) = getimagesize($photo);
			$this->replaceStack[$token]=sprintf('<img id="photo" name="photo" src="%s" style="width: %dpx; height: %dpx;"/>',$photo,$width,$height);
    } else {
      $this->replaceStack[$token]=sprintf('<div style="width: 35mm; height:45mm; border: black solid 1px;" id="photoarea"></div>');
    }
  }

  function radioset ($token,$name,$value,$label) {
    if ($this->renderPdf){
      if(isset($this->data[$name])&&$this->data[$name]==$value) {
        $this->replaceStack[$token]=sprintf('<tr><td>%s</td></tr>',$label);
      } 
	    // Do not show the unchecked options for a radioset
	    else {
        $this->replaceStack[$token]='';
      }
    } else {
      $this->replaceStack[$token]=sprintf('<label><input type="radio" id="%s" name="%s" value="%s"/>%s</label>',$name,$name,$value,$label);
    }
  }

  function dropdown ($token,$name,$values,$default='choose',$label='') {
		$valueArray=split(',',$values);
	  $combobox=$valueArray[0];
    if ($this->renderPdf) {
			if ($this->data[$name]!=$default) {
				if ($label=='') {
					$this->replaceStack[$token]=$this->$combobox->printValue($this->data[$name]);
				} else {
					$this->replaceStack[$token]=sprintf('<tr><td>%s %s</td></tr>',$label,$this->$combobox->printValue($this->data[$name]));
				}
			} else {
				$this->replaceStack[$token]='';
			}
    } else {
			$options=$this->$combobox->options($default);
			if ($label=='') {
				$this->replaceStack[$token]=sprintf('<select id="%1$s" name="%1$s">%2$s</select>',$name,$options);
  		} else {
				$this->replaceStack[$token]=sprintf('<label id="%1$s_label">%3$s<select id="%1$s" name="%1$s">%2$s</select></label>',$name,$options,$label);
			}
    }
  }

  function checkbox ($token,$name,$value) {
    if (($this->renderPdf)) {
      if (isset($this->data[$name])&&$this->data[$name]=='true') {
        $this->replaceStack[$token]=sprintf('%s',$value);
      } else {
        $this->replaceStack[$token]='';
      }
    } else {
      $this->replaceStack[$token]=sprintf('<label><input type="checkbox" id="%s" name="%s" value="true" />%s</label>',$name,$name,$value);
    }
  }

	function location ($token) {
		$this->replaceStack[$token]=$this->location;
  }


  function datepicker($token,$name) {
    if ($this->renderPdf) {
      $this->replaceStack[$token]=$this->data[$name];
		} else {
			$this->replaceStack[$token]=sprintf('<input type="text" id="%1$s" name="%1$s" class="date-pick"/>',$name);
		}
	}

  function fillin ($token,$name) {
    if ($this->renderPdf) {
			try {
				if (isset($this->data[$name]) && !is_null($this->data[$name])) {
          $this->replaceStack[$token]=$this->data[$name];
				}
			} catch (Exception $e){
				echo $e.' iets met veld '.$name;
			}
    } else {
      $this->replaceStack[$token]=sprintf('<input type="text" id="%s" name="%s" />',$name,$name);
    }
  }

  function pdf_create($filename,$content)
  {
		try {
			$dompdf = new DOMPDF();
			$dompdf->load_html($content);
			$dompdf->render();
			file_put_contents($filename,$dompdf->output());
		} catch(Exception $e) {
			die('PDF rendering failed. Please call UISS directly.');
		}
  }

  function debug($data,$title='debug') {
		echo "<fieldser><legend>$title</legend><pre>";
    print_r($data);
    echo "</pre></fieldset>";		
  }
  
  
  
}

?>