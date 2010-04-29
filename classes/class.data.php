<?php

  require_once(RELATIVE_PATH.'classes/class.xmlarray.php');  

  class dataArray extends xmlArray {

    function __construct($table) {
      parent::__construct($table);
    }

    function getJsonOptions() {
      return json_encode($this->data);
    }

    function options() {
      $options = '';
      foreach ($this->data as $key=>$value) {
        $options.=sprintf('<option value="%1$s">%2$s</option>',$key,$value);
      }
      return $options;
    }
    
    function printValue($key) {
      return $this->data[$key];
    }
    
  }
  
?>