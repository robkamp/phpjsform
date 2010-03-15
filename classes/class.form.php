<?php

require_once(RELATIVE_PATH.'classes/class.phpmailer.php');
require_once(RELATIVE_PATH.'classes/class.data.php');
require_once(RELATIVE_PATH.'classes/class.utils.php');
require_once(RELATIVE_PATH.'plugins/dompdf/dompdf_config.inc.php');

define('DATA_PATH','data/');
define('TEMP_PATH','tmp/');

class form {
  private $content;
  private $renderPdf=false;
  private $data;
  private $keywords=array('language','language_other','data','checkbox','radioset','datepicker','fill-in','dropdown','fileupload','submit','location','debug','disclaimer');
  private $errors=array();
  private $replaceStack=array();
  private $countries;
  private $sessions;
  private $location;
  private $session;
  private $method;
  private $debug=false;
  private $emailSettings = array();
  
  private $form;
  private $pdf;
  private $finished;
  private $htmlmailstudent;
  private $htmlmaildirector;
  
  function __construct() {
  
	// Load the ini file for this form
	$settings = parse_ini_file (RELATIVE_PATH.'/form.ini', true);
	
	$this->form = RELATIVE_PATH.$settings['forms']['form'];
	$this->pdf = RELATIVE_PATH.$settings['forms']['pdf'];
	$this->finished = RELATIVE_PATH.$settings['forms']['finished'];
	$this->htmlmailstudent = RELATIVE_PATH.$settings['forms']['email_student'];
	$this->htmlmaildirector = RELATIVE_PATH.$settings['forms']['email_director'];
	
    if (file_exists($this->form) && 
	    file_exists($this->pdf) && 
		file_exists($this->finished) && 
		file_exists($this->htmlmailstudent)&& 
		file_exists($this->htmlmaildirector)) {
      $this->filename = '';
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
	  $this->emailSettings['from'] = $settings['e-mail']['director'];
    } else {
      die(sprintf("One of the following forms does not exist %s, %s, %s, %s, %s",$this->form,$this->pdf,$this->finished,$this->htmlmailstudent,$this->htmlmaildirector));
    }
  }

  function parse ($method='get') {

    date_default_timezone_set('UTC');  // Set the timezone to be UTC, must be here inside the class

    switch ($method) { // Depending on the method
      case 'post': { // for post 
        $this->data=array_merge($_REQUEST,$_SESSION); // Merge the session data with the request data
        $this->trimData(); // Trim All data in the array 
        $content = file_get_contents($this->pdf); // Get the contents for generating the PDF


        $this->renderPdf=true; // This time around we render the PDF

		// TODO: Provide a hook to do the stuff below

        // prepare the filename without special characters
        $this->filename = utils::postSlug(sprintf('%s_%s_%s_%s',$_SESSION['DATETIME'],$this->data['family_name'],$this->data['first_name'],$this->data['date_of_birth'])); 
        $this->filename = sprintf('%s%s',DATA_PATH,$this->filename); // prepend the path for the data directory
		
        // Now that we are rendering the PDF we should move 
        // the image from its temporary name to its final name
        // rename (RELATIVE_PATH.DATA_PATH.$_SESSION['GUID'].'.jpg',RELATIVE_PATH.$this->filename.'.jpg');
        rename ($_SESSION['Photo'],RELATIVE_PATH.$this->filename.'.jpg'); // rename/move the resized photo
        rename ($_SESSION['OriginalPhoto'],RELATIVE_PATH.$this->filename.'_original.'.$_SESSION['OriginalExtension']); // move the original photo
        break;
      }
      case 'get': { // for get
        $_SESSION['GUID']=utils::uuid(); // Determine the GUID
        $_SESSION['DATETIME']=date('YmdHi'); // Set the date and time we started this session
        $content = file_get_contents($this->form); // Get the form we have to parse 
        $this->renderPdf=false; // Do not render the PDF this time 
        $this->data=array(); // Prepare a clean data array
        break; 
      }
    }

    $this->data['location']=$this->location; // remember the location 
    $this->data['filename']=$this->filename; // remember the filneame
    $this->data['cache_limiter']=session_cache_limiter(); // what cache limiter is used
    $this->data['cache_expire']=session_cache_expire(); // when should the session expire 
    $this->data['HTTP_USER_AGENT']=$_SERVER['HTTP_USER_AGENT']; // what agent did connect to us
    
	if (isset($_REQUEST['debug'])&&$_REQUEST['debug']=='2wsxCDE') { // Debugging mode only with a password
      $this->debug = true; // Debugging is on
    }

    $tokens=preg_split('/{|}/',$content); // Split the content into tokens, wow a one operation tokenizer, this takes about one a4 worth of code in Progress ABL

    foreach ($tokens as $key=>$token) { // Walk to tokens
      $keyword=''; // initialize the keyword 
      $name=''; // initialize the name
      $value=''; // initialize the value
      $default=''; // initialize the default
      $label=''; // initialize the label
      
      switch (count(preg_split('/ /',$token))) { // Based on the number of tokens within a token
        case 0: 
          throw new Exception('Cannot render token '.$token); // We cannot render a token that only exists of {}
          break;
        case 1: 
          $keyword = $token; // The token is a single keyword without parameters
          break;
        case 2:
          list($keyword,$name) = preg_split('/ /',$token); // Split the token in a keyword and a name
          break;
        default:
          list($keyword,$name,$value) = preg_split('/ /',$token,3); // Split the token in a keyword and a name and a value that is the 3rd and consecutive tokens
          break;
      }
      
      if (in_array($keyword,$this->keywords)) { // Handle the keywords, pass token to all renderers as they replace the token with the rendered content
        switch ($keyword) {
          case 'debug':
            $this->debuginfo($token); // write debugging info to the form 
            break;
          case 'disclaimer': 
            $this->disclaimer($token); // write a disclaimer to the form, ie sucks
            break;
          case 'location':
            $this->location($token); // write the location to the form
            break;
          case 'fileupload':
            $this->fileupload($token,$name); // write a fileupload dialog to the form
            break;
          case 'checkbox':
            $this->checkbox($token,$name,$value); // write a checkbox to the form
            break;
          case 'radioset':
             list($keyword,$name,$value,$label) = preg_split('/ /',$token,4); // A radio set has four parameters 
            $this->radioset($token,$name,$value,$label); // write the radio set to the form
            break;
          case 'datepicker':
            $this->datepicker($token,$name); // Write a datepicker to the form
            break;
          case 'language_other':
            $this->language_other($token,$name); // Write another language to the form, only called in renderPdf mode
          break;
          case 'language':
            list($keyword,$name,$label) = preg_split('/ /',$token,3); // for language the third parameters is its label
            $this->language($token,$name,$label); // Write another language to the form, only called in renderPdf mode
          break;
          case 'data':
            $this->data($token,$name); // Write the data of an element to the form, only called in renderPdf mode 
          break;
          case 'fill-in':
            if ($this->renderPdf) { // Fill ins work different in renderPdf mode
              list($keyword,$name,$label) = preg_split('/ /',$token,3); // The third parameter is the label
              $this->fillin($token,$name,$label); // render the fill in 
            } else {
              $this->fillin($token,$name); // render the fill in
            }
            break;
          case 'dropdown':
            switch((count(preg_split('/ /',$token)))) { // We have different tastes of dropdowns
              case 0:
              case 1:
              case 2:
                die($token); // We cannot render dropdowns with 0, 1 or 2 parameters
              break;
              case 3:
                list($keyword,$name,$value) = preg_split('/ /',$token); // The third tokens is the value
                $this->dropdown($token,$name,$value); // render the dropdown
              break;
              case 4:
                list($keyword,$name,$value,$default) = preg_split('/ /',$token); // the fourth parameter is the default 
                $this->dropdown($token,$name,$value,$default); // render the dropdown 
              break;
              default: 
                list($keyword,$name,$value,$default,$label) = preg_split('/ /',$token,5); // the fifth and consecutive parameter is the label 
                $this->dropdown($token,$name,$value,$default,$label); // render the dropdown
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
    foreach ($this->data as $key=>$value) { // Walk the data
      $_SESSION[$key] = $value; // Place the value on the data array
    }
    
    if ($this->renderPdf) { // if we are to render the pdf
      $dat = '';
      foreach ($this->data as $key=>$value) { // Walk all items in the data
        $dat.=sprintf('%s=%s'.chr(10),$key,$value); // Generate the data file contents
      };
      $filebase=sprintf('%s%s',RELATIVE_PATH,$this->filename); // Create the base filename
      
      file_put_contents($filebase.'.dat',$dat); // Save the contents of the data
      
      $content = utils::removeWhitespace($content);  // remove all the superfluous whitespace
      
      file_put_contents($filebase.'.html',$content); // Save the HTML that is used for generating the PDF
      
      $this->pdf_create($filebase); // Generate the PDF 
      
      $this->sendMailDirector($filebase.'.pdf',$filebase.'.dat',$filebase.'.jpg'); // Send an email to the Director
      $this->sendMailStudent($filebase.'.pdf'); // Send an email to the student 
      
      $content = file_get_contents($this->finished); // Get the contents for the OK screen
      foreach ($this->replaceStack as $search=>$replace) { // Walk all items in the stack
        $content=str_replace('{'.$search.'}',$replace,$content);  // Replace the items
      }
      session_destroy(); // Destroy the session
    } 
    print $content; // Send the content to the browser
  }

  function debuginfo($token) {
    if ($this->debug) { 
      $this->replaceStack[$token]=sprintf($this->setting['info']['debuginfo'],$_SESSION['GUID']);
    } else {
      $this->replaceStack[$token]='';
    }
  }

  function disclaimer($token) {
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
      $this->replaceStack[$token]=$this->setting['info']['iedisclaimer'];
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
      if ($width == 0 && $height == 0)  {
        die("Something went wrong while rendering your photo, please send a mail to director@uiss.org about this error.");
      } else {
        $this->replaceStack[$token]=sprintf('<img id="photo" name="photo" src="%s" style="width: %dpx; height: %dpx;"/>',$photo,$width,$height);
      }
    } else {
      $this->replaceStack[$token]=sprintf('<div style="width: 35mm; height:45mm; border: black solid 1px;" id="photoarea"></div>');
    }
  }

  function radioset ($token,$name,$value,$label) {
    if ($this->renderPdf){
      if(isset($this->data[$name])&&$this->data[$name]==$value) {
        $this->replaceStack[$token]=sprintf('<tr><td>%s</td></tr>',$label);
      } 
      else {
        $this->replaceStack[$token]='';
      }
    } else {
      $this->replaceStack[$token]=sprintf('<label><input type="radio" id="%s" name="%s" value="%s"/>%s</label>',$name,$name,$value,$label);
    }
  }

  function dropdown ($token,$name,$values,$default='choose',$label='') {
    $valueArray=preg_split('/,/',$values);
    $combobox=$valueArray[0];
    if ($this->renderPdf) {
      if ($this->data[$name]!=$default) {
        if (empty($label)) {
          $this->replaceStack[$token]=$this->$combobox->printValue($this->data[$name]);
        } else {
          $this->replaceStack[$token]=sprintf('<tr><th>%s</th><td>%s</td></tr>',$label,$this->$combobox->printValue($this->data[$name]));
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
        $this->replaceStack[$token]=sprintf('<tr><th>%s</th></tr>',$value);
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

  function data ($token,$name) {
    if ($this->renderPdf && isset($this->data[$name])) {
      $this->replaceStack[$token]= $this->data[$name];
    } else {
      $this->replaceStack[$token]= '';
    }
  }

  function language_other ($token,$name) {
    if ($this->renderPdf) {
      if (isset($this->data[$name]) && !empty($this->data[$name])) {
        $this->replaceStack[$token]= sprintf('<tr>
        <td id="%1s_label" class="fill-in">%2s</td>
        <td>%3s</td>
        <td>%4s</td>
        <td>%5s</td>
      </tr>',
                                            $name,
                                            $this->data[$name],
                                            $this->data[$name.'_reading'],
                                            $this->data[$name.'_speaking'],
                                            $this->data[$name.'_writing']);
      } else {
        $this->replaceStack[$token] = '';
      }
    } else {
      $this->replaceStack[$token] = '';
    }
  }

  
  function language ($token,$name,$label) {
    if ($this->renderPdf) {
      if ($this->data[$name.'_reading'] != 'not' || $this->data[$name.'_speaking'] != 'not' || $this->data[$name.'_writing'] != 'not' ) {
        $this->replaceStack[$token] = sprintf('<tr>
        <td id="%1s_label">%2s</td>
        <td>%3s</td>
        <td>%4s</td>
        <td>%5s</td>
      </tr>',
                                            $name,
                                            $label,
                                            $this->data[$name.'_reading'],
                                            $this->data[$name.'_speaking'],
                                            $this->data[$name.'_writing']);
      } else {
        $this->replaceStack[$token] = '';
      }
    } else {
      $this->replaceStack[$token] = '';
    }
  }
  
  function fillin ($token,$name,$label='') {
    if ($this->renderPdf) {
      try {
        if (isset($this->data[$name]) && !empty($this->data[$name])) {
          $this->replaceStack[$token]= sprintf('<tr>
        <td class="label" id="%1s_label">
           %2s
        </td>
        <td class="fill-in">
          %3s
        </td>
      </tr>',$name,$label,$this->data[$name]);
        } else {
          $this->replaceStack[$token] = '';
        }
      } catch (Exception $e){
        echo $e.' iets met veld '.$name;
      }
    } else {
      $this->replaceStack[$token]=sprintf('<input type="text" id="%s" name="%s" />',$name,$name);
    }
  }

  function pdf_create($filename)
  {
    try {
      $dompdf = new DOMPDF();
      $dompdf->load_html_file($filename.'.html');
      $dompdf->render();
      file_put_contents($filename.'.pdf',$dompdf->output());
    } catch(Exception $e) {
      die('PDF rendering failed. Please call UISS directly or use the downloadable PDF file on the site.');
    }
  }

  function trimData () {
    // Trim all whitespace from the beginning and the end of the values 
    // in the data array
    foreach ($this->data as $key=>$value) {
      $this->data[$key] = preg_replace(array('/^\s/','/\s$/'),array('',''),$value);
    }
  }
    
}

?>