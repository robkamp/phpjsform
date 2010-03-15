<?php

  require_once(RELATIVE_PATH.'classes/class.csvhandler.php');  

  class dataArray extends csvHandler {
    
    function __construct($table) {
      parent::__construct(RELATIVE_PATH.'config/'.$table.'.csv');
    }
    
    function options($selected='choose') {
      $options='';
      foreach ($this->data as $key=>$value) {
        $options.=sprintf('<option%s value="%s">%s</option>',($selected==$key?' selected':''),$key,$value);
      }
      return $options;
    }
    
    function printValue($key) {
      return $this->data[$key];
    }
    
  }
  
?>