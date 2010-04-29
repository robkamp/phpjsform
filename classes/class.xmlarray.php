<?php

  class xmlArray {
    
    protected $data=array();
    protected $table;
    
    private $file;
    private $xmlParser;
    private $key;
    private $element;
    private $inside_data;
    
    function __construct($table) {
      $this->load($table);
    }

    private function load ($table) {

      $this->table = $table;
      $file = RELATIVE_PATH.'config/'.$this->table.'.xml';
      
      if (file_exists($file)) { // If the file exists parse it
      
        $this->file = $file;
        $this->xmlParser=xml_parser_create();
      
        xml_set_object($this->xmlParser,$this); # enable parser within object
        
        xml_set_element_handler($this->xmlParser,'startElement','endElement');
        
        xml_set_character_data_handler($this->xmlParser,'tagData');

        xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8'); 
        xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 1); 
        xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 0);
        xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_TAGSTART, 0);
        
        $xmlData = file_get_contents($this->file);
        
        $this->data=array();
        try {
          xml_parse($this->xmlParser,$xmlData);
        } catch (Exception $e) {
          trigger_error(sprintf('Cannot parse %s into array, due to %s.',$file,$e->getMessage()),E_USER_ERROR);
        }

        natcasesort($this->data);
        $this->data = array("choose" => "Choose")+$this->data;
        xml_parser_free($this->xmlParser);

        return $this->data;
        
      } else {
        trigger_error(sprintf('xml file %s does not exist',$file),E_USER_ERROR);
      }
    }
    
    private function startElement($parser, $name, array $attrs) {
      $this->element=$name; 
			$this->inside_data = false;
    }
    
    private function endElement($parser, $name) {
      $this->element='';
      $this->inside_data = false;
    }
    
    private function tagData ($parser, $data) {
      if (!empty($data)) {
        // trigger_error ($this->element .' '. $data); 
        if ($this->element=='KEY') {
					$this->key=$data;
        }
        if ($this->element=='VALUE') {
          if ($this->inside_data) {
            $this->data[$this->key].=$data;
          } else {
            $this->data[$this->key]=$data;
          }
        }
      }
      $this->inside_data = true;
    }
  }

?>