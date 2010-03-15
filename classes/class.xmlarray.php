<?php

  class xmlArray {
    
    private $xmlData;
    private $xmlArray=array();
    private $values=array();
    private $xmlParser;
    private $key;
    private $attrs;
    private $element;
    private $inside_data;
    
    function xmlArray() {
    
      $this->xmlParser=xml_parser_create();
    
      xml_set_object($this->xmlParser,$this); # enable parser within object
      
      xml_set_element_handler($this->xmlParser,'startElement','endElement');
      
      xml_set_character_data_handler($this->xmlParser,'tagData');

      xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8'); 
      xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 1); 
      xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 0);
      xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_TAGSTART, 0);
    }

    function __destruct(){
      xml_parser_free($this->xmlParser);      
    }
    
    function parseXmlArray ($xmlFile) {
      if (file_exists($xmlFile)) {

        // $handle = fopen($xmlFile, "rb");
        // $xmlData = fread($handle, filesize($xmlFile));
        // fclose($handle);
        
        $xmlData = file_get_contents($xmlFile);
        
        $this->xmlArray=array();
        try {
          xml_parse($this->xmlParser,$xmlData);
        } catch (Exception $e) {
          trigger_error(sprintf('Cannot parse %s into array, due to %s.',$xmlFile,$e->getMessage()),E_USER_ERROR);
        }

				natcasesort($this->xmlArray);
				$this->xmlArray = array("choose" => "Choose")+$this->xmlArray;
        return $this->xmlArray;
      } else {
        trigger_error(sprintf('XML file %s does not exist',$xmlFile),E_USER_ERROR);
      }
    }
    
    function startElement($parser, $name, array $attrs) {
      $this->element=$name; 
			$this->attrs=$attrs;
			$this->inside_data = false;
    }
    
    function endElement($parser, $name) {
      $this->element='';
      $this->inside_data = false;
    }
    
    function tagData ($parser, $data) {
      if (!empty($data)) {
        trigger_error ($this->element .' '. $data); 
        if ($this->element=='KEY') {
					$this->key=$data;
        }
        if ($this->element=='VALUE') {
          // debug($data,$this->key);
          if ($this->inside_data) {
            $this->xmlArray[$this->key].=$data;
          } else {
            $this->xmlArray[$this->key]=$data;
          }
        }
      }
      $this->inside_data = true;
    }
  }

?>