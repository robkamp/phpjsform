<?php

  class csvHandler {
    
    protected $data = array();
    protected $table = '';
    protected $options = '';
    private $default = '';
    
    function __construct ($table) { // Constructor method always pass a file
      $this->load($table);
    }
    
    private function load ($table) {
      $this->table = $table;
      $file = RELATIVE_PATH.'config/'.$this->table.'.csv';
      if (file_exists($file)) { // If the file exists parse it

        $csvData = file_get_contents($file);
        $csvRecords = preg_split('/\r\n|\r/',$csvData);
        
        // Now get the first line of the data
        // determine whether it has two or three parameters
        if ($this->hasDefault($csvRecords)) {
          $this->loadWithDefault($csvRecords);
        } else {
          $this->data['choose'] = 'Choose';
          $this->loadWithoutDefault($csvRecords);
        }
      } else {
        trigger_error(sprintf('CSV file %s does not exist',$file),E_USER_ERROR);
      }
    }

    private function loadWithDefault($csvRecords) {
      
      foreach ($csvRecords as $record) {
        if (!empty($record)) {
          list($defaut,$key,$value) = preg_split('/,|;/',$record);
          if ($this->default=='') {
            $this->default = $key;
          } else {
            $this->data[$key] = $value;
          }
        }
      }
    }
    
    private function hasDefault ($csvRecords) {
      return count(preg_split('/,|;/',$csvRecords[0]))==3;
    }
    
    private function loadWithoutDefault($csvRecords) {
      $skippedfirstline = false;
      foreach ($csvRecords as $record) {
        if (!empty($record)) {
          if ($skippedfirstline) {
            list($key,$value) = preg_split('/,|;/',$record);
            $this->data[$key] = $value;
          } else {
            $skippedfirstline = true;
          }
        }
      }
    }
    
  }

?>