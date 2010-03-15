<?php

date_default_timezone_set('UTC');

define('RELATIVE_PATH',$_SERVER['DOCUMENT_ROOT'].'/');

class form {

  private $form;
  private $finished;
  private $content;
  private $renderPdf=false;
  private $data;
  private $keywords=array('checkbox','radioset','datepicker','fill-in','dropdown','fileupload','captcha','submit','location');
  private $errors=array();
  private $replaceStack=array();
  private $countries;
  private $sessions;
  private $session;
	private $htmlmail;
  private $again=false;
  private $method;


  function form($form,$finished,$pdf,$htmlmail) {
    if (file_exists($form) && file_exists($finished) && file_exists($pdf) && file_exists($htmlmail)) {
      $this->form = $form;
      $this->pdf = $pdf;
      $this->finished = $finished;
			$this->htmlmail = $htmlmail;
      $this->fluency2 = new dataArray('fluency2');
      $this->fluency1 = new dataArray('fluency1');
      $this->titles = new dataArray('titles');
      $this->countries = new dataArray('countries');
      $this->usstates = new dataArray('usstates');
      $this->nationalities = new dataArray('nationalities');
      $this->sessions = new dataArray('sessions');
      $this->afternoon1 = new dataArray('afternoon1');
      $this->afternoon2 = new dataArray('afternoon2');
      $this->morning = new dataArray('morning');
    } else {
      die(sprintf("Form %s does not exist",$form));
    }
  }

  function parse ($method='get') {

    session_start();
		
		if (!isset($_SESSION['GUID'])) {
      $_SESSION['GUID']=utils::uuid();
			$_SESSION['DATETIME']=date('YmdHi');
    } else {
			if ($method=='post') {
    		$this->data=array_merge($_SESSION,$_REQUEST);
			}
		}
    
    if ($method=='post') {
      $content = file_get_contents($this->pdf);
      $this->renderPdf=true;
    } else {
      $content = file_get_contents($this->form);
      $this->renderPdf=false;
    }

    $tokens=preg_split('/{|}/',$content);

    foreach ($tokens as $key=>$token) {
      $keyword='';
      $name='';
      $value='';
			$default='choose';
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
        case 4:
          list($keyword,$name,$value,$default) = split(" ",$token);
          break;
        default:
          list($keyword,$name,$value) = split(" ",$token,3);
          break;
      }

      if (in_array($keyword,$this->keywords)) {
        switch ($keyword) {
          case 'location':
            $this->location($token);
            break;
          case 'captcha':
            $this->captcha($token);
            break;
          case 'submit':
            $this->submit($token);
            break;
          case 'fileupload':
            $this->fileupload($token,$name);
            break;
          case 'checkbox':
            $this->checkbox($token,$name,$value);
            break;
          case 'radioset':
            $this->radioset($token,$name,$value);
            break;
          case 'datepicker':
            $this->datepicker($token,$name);
            break;
          case 'fill-in':
            $this->fillin($token,$name);
            break;
          case 'dropdown':
            $this->dropdown($token,$name,$value,$default);
            break;
        }
      }
    }

    foreach ($this->replaceStack as $search=>$replace) {
      $content=str_replace('{'.$search.'}',$replace,$content);
    }
		
    if ($this->renderPdf) {
			$dat =join(';',array_keys($this->data)).chr(10);
      $dat.=join(';',$this->data);
      $file=sprintf('%sdata/%s_%s_%s_%s',
										RELATIVE_PATH,
										$_SESSION['DATETIME'],
										$this->data['family_name'],
										$this->data['first_name'],
										$this->data['date_of_birth']);

			file_put_contents($file.'.dat',$dat);
//      file_put_contents($file.'.html',$content);
			
			$this->pdf_create($file.'.pdf',$content);
      $this->sendMail($file.'.pdf');
			$content = file_get_contents($this->finished);
			foreach ($this->replaceStack as $search=>$replace) {
				$content=str_replace('{'.$search.'}',$replace,$content);
			}			
			// session_destroy();
    }
    print $content;
		
  }

  function sendMail($fileatt) {

		$content = file_get_contents($this->htmlmail);
		foreach ($this->replaceStack as $search=>$replace) {
			$content=str_replace('{'.$search.'}',$replace,$content);
		}			

		$mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
		try {
			$from    = "director@uiss.org";
			if ($_SERVER['SERVER_NAME']=='uiss.nl.eu.org') {
				$from    = "ruudschouwenaar@hotmail.com";
			} 
			$mail->AddCC($from, 'Director UISS');
			$mail->AddReplyTo($from, 'Director UISS');
			$mail->AddAddress($this->data['e-mail'],sprintf('%1$s, %2$s',$this->data['family_name'],$this->data['first_name']));
			$mail->SetFrom($from, 'Director UISS');
			$mail->Subject = 'Enrolment to UISS course';
			$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
			$mail->MsgHTML($content);
			$mail->AddAttachment($fileatt);      // attachment
			$mail->Send();
		} catch (phpmailerException $e) {
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
  }

  function fileupload($token,$name) {
    if ($this->renderPdf) {
			$photo=sprintf('http://'.$_SERVER['SERVER_NAME'].'/data/%s_%s_%s_%s.jpg',
										 $_SESSION['DATETIME'],
										 $this->data['family_name'],
										 $this->data['first_name'],
										 $this->data['date_of_birth']);
			list($width,$height,$type,$flag) = getimagesize($photo);
			$this->replaceStack[$token]=sprintf('<img id="photo" name="photo" src="%s" style="border: solid black 1px; width: %dpx; height: %dpx;"/>',$photo,$width,$height);
    } else {
      $this->replaceStack[$token]=sprintf('<div style="width: 35mm; height:45mm; border: black solid 1px;" id="photoarea"></div>');
    }
  }

  function radioset ($token,$name,$value) {
    if ($this->renderPdf){
      if(isset($this->data[$name])&&$this->data[$name]==$value) {
        $this->replaceStack[$token]=sprintf('<input disabled readonly checked type="radio" id="%s" name="%s" value="%s" />',$name,$name,$value);
      } else {
        $this->replaceStack[$token]=sprintf('<input disabled readonly type="radio" id="%s" name="%s" value="%s" />',$name,$name,$value);
      }
    } else {
      $this->replaceStack[$token]=sprintf('<input type="radio" id="%s" name="%s" value="%s" />',$name,$name,$value);
    }
  }

  function dropdown ($token,$name,$values,$default='choose') {
		$valueArray=split(',',$values);
	  $combobox=$valueArray[0];
    if ($this->renderPdf) {
			$this->replaceStack[$token]=$this->$combobox->printValue($this->data[$name]);
    } else {
			$options=$this->$combobox->options($default);
			$this->replaceStack[$token]=sprintf('<select id="%s" name="%s">%s</select>',$name,$name,$options);
    }
  }

  function checkbox ($token,$name,$value) {
    if (($this->renderPdf)) {
      if (isset($this->data[$name])&&$this->data[$name]=='true') {
        $this->replaceStack[$token]=sprintf('<input disabled readonly checked type="checkbox" id="%s" name="%s" value="true" />%s',$name,$name,$value);
      } else {
        $this->replaceStack[$token]=sprintf('<input disabled readonly type="checkbox" id="%1$s" name="%1$s" value="true" />%2$s',$name,$value);
      }
    } else {
      $this->replaceStack[$token]=sprintf('<input type="checkbox" id="%s" name="%s" value="true" />%s',$name,$name,$value);
    }
  }

	function location ($token) {
		$this->replaceStack[$token]=sprintf('http://%s',$_SERVER['SERVER_NAME']);
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
      $this->replaceStack[$token]=$this->data[$name];
    } else {
      $this->replaceStack[$token]=sprintf('<input type="text" id="%s" name="%s" />',$name,$name);
    }
  }

  function captcha ($token) {
    //      if ($this->method=='get') {
    //        $this->replaceStack[$token]=sprintf('<input type="text" id="%s" name="%s" />','captcha','captcha');
    //      }
    $this->replaceStack[$token]='';
  }

  function pdf_create($filename,$content)
  {
		try {
			$dompdf = new DOMPDF();
			//debug('Hier ab');
			//$dompdf->set_paper("A4", "portrait"); 
			$dompdf->load_html($content);
			$dompdf->render();
			file_put_contents($filename,$dompdf->output());
		} catch(Exception $e) {
			debug($e,'PDF rendering failed');
			die('PDF rendering failed');
		}
  }

}

require_once(RELATIVE_PATH.'classes/class.phpmailer.php');
require_once(RELATIVE_PATH.'classes/class.data.php');
require_once(RELATIVE_PATH.'classes/class.utils.php');
require_once(RELATIVE_PATH.'plugins/dompdf/dompdf_config.inc.php');


?>
