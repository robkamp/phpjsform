<?php

	error_reporting(E_ALL);

	define('APPLICATION_PATH','/application/');
  define('RELATIVE_PATH',$_SERVER['DOCUMENT_ROOT'].APPLICATION_PATH);

  require_once(RELATIVE_PATH .'classes/class.utils.php');
  require_once(RELATIVE_PATH .'classes/class.form.php');    

  if (!isset($_REQUEST['debug'])&&file_exists(RELATIVE_PATH.'OFFLINE')) {
    echo '<html><body><h1>We are temporarily offline. Please excuse us for the inconvencience.</h1></body></html>';
  } else {

    $form = new form(RELATIVE_PATH .'html/formulier_template_v07.html',
                     RELATIVE_PATH .'html/finished_template_v05.html',
                     RELATIVE_PATH .'pdf/pdf_template_v09.html',
                     RELATIVE_PATH .'email/email_template_student.html',
                     RELATIVE_PATH .'email/email_template_director.html');

    session_cache_limiter('private'); // Set the cache limiter to private
    session_cache_expire(30); // set the cache expire to 30 minutes
    session_start(); // Continue the session

    if (isset($_REQUEST['family_name']) && isset($_SESSION['GUID'])) {
      $form->parse('post'); // Process the entered data
      
    } else {
      session_destroy(); // If there is a session please destroy it

      session_cache_limiter('private'); // Set the cache limiter to private
      session_cache_expire(30); // set the cache expire to 30 minutes
      session_start(); // Start the session

      $form->parse('get'); // Show the form

    }
                     
  }


?>

