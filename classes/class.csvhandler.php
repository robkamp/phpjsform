<?php

  class csvHandler {
    
    protected $data = array();
    private $default = '';
    private $csvFile = '';
    
    function __construct ($csvFile) { // Constructor method always pass a file
      $this->load($csvFile);
    }
    
    private function load ($csvFile) {
      if (file_exists($csvFile)) { // If the file exists parse it

        $this->csvFile = $csvFile;
        $csvData = file_get_contents($this->csvFile);
        $csvRecords = preg_split('/\r\n|\r/',$csvData);
        
        // Now get the first line of the data
        // determine whether it has two or three parameters
        if ($this->hasDefault($csvRecords)) {
          $this->loadWithDefault($csvRecords);
        } else {
          $this->loadWithoutDefault($csvRecords);
          $this->data['choose'] = 'Choose';
        }
      } else {
        trigger_error(sprintf('CSV file %s does not exist',$csvFile),E_USER_ERROR);
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