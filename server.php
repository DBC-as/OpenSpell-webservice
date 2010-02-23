<?php
require_once("ws_lib/xml_func_class.php");
require_once("ws_lib/webServiceServer_class.php");
class spellServer extends webServiceServer
{
  public function openSpell($params)
  {
    $met = new methods($this->config,$this->verbose);
    $terms=$met->spell_check($params);
   
    $response_xmlobj->spellResponse->_namespace="http://oss.dbc.dk/ns/openspell";    
    foreach( $terms as $term )
      $response_xmlobj->spellResponse->_value->term[]=$term;

    return $response_xmlobj;
  }

  /*  protected function create_sample_forms()
  {
    Header( "HTTP/1.1 303 See Other" );
    Header( "Location: example.php" );
    exit;
    }*/

    /** \brief Echos config-settings
   *
   */
  public function show_info() 
  {
    echo "<pre>";
    echo "version             " . $this->config->get_value("version", "setup") . "<br/>";
    echo "log                 " . $this->config->get_value("logfile", "setup") . "<br/>";
    echo "spellcheck          " . $this->config->get_value("spell_url", "setup") . "<br/>";
    echo "</pre>";
    die();
  }
  
}


$server = new spellServer("openspell.ini");
$server->handle_request();

/**
 * Class containing spellcheck functions  
 */                                       
class methods                             
{                                         

  private $verbose;
  private $config;

  public function __construct($config,$verbose=null)
  {
    $this->verbose=$verbose;
    $this->config=$config;   
  }  
                      
  public function spell_check($params)                              
  {
    
    $curl=new curl();   

    $url = $this->config->get_value("spell_url","setup");

    $curl->set_url($url);                                     

    $curl->set_option(CURLOPT_HTTPHEADER, $this->getSoapHeader());
    $curl->set_option(CURLOPT_POST, 1);                           
    $curl->set_option(CURLOPT_POSTFIELDS,$this->getSoapBody($params));    

    $ret=$curl->get();

    $status = $curl->get_status();
    if( $status['error'] )        
      {
	if( $verbose )
	  $verbose->log($status['error']);
        return false;                 
      }                               

    if( $status['http_code']!= 200 )
      {
	if( $verbose )
	  $verbose->log("Error from curl class: http-code: ".$status['http_code']);
        return false;                                                                         
      }                                                                                       
                                                                                              
    return $this->ParseResult($ret);                                                          
  }                                                                                           

  /**
   *  This method uses response_parser class to parse the xml from Ankiro spellcheck. 
   *  response_parser class generates an object of SpellResponse type.                
   *                                                                                  
   *  params:                                                                         
   *  $xml: the soap response from Ankiro spellcheck                                  
   *  return: response mapped to SpellResponse class                                  
   */                                                                                 
  private function ParseResult(&$xml)                                                 
  {
    $parser = new spellcheck_parser($xml);                                            
    
    // check for errors
    if( $parser )      
      return $parser->term;

    if( $verbose )
      $verbose->log($parser->error);

    return false;               
  }                             

  /** 
   *   returns the xml for making a soap request to Ankiro spellcheck
   */                                                                
  private function getSoapBody($params)                        
  {
      // get defaults from config
    $defaults = $this->config->get_section("defaults");
                    
    $xml='                                                           
    <?xml version="1.0" encoding="utf-8"?>                           
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema"                         
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">              
<soap:Body>                                                          
<Spellcheck xmlns="http://www.ankiro.dk/AnkiroLanguageServices/Spellcheck">
        <Word>'.$params->word->_value.'</Word>                                    
        <LCID>'.$defaults["LCID"].'</LCID>                           
        <ThesaurusFriendlyName>'.$defaults['ThesaurusFriendlyName'].'</ThesaurusFriendlyName>
        <MaxNumSuggestions>'.$params->number->_value.'</MaxNumSuggestions>          
        <MaxPermutations>'.$defaults["MaxPermutations"].'</MaxPermutations>
        <MinWeight>'.$defaults["MinWeight"].'</MinWeight>                  
        <SpellcheckOnlyIfNotExists>'.$defaults["SpellCheckOnlyIfNotExists"].'</SpellcheckOnlyIfNotExists>
        <InNormalization>'.$defaults["InNormalization"].'</InNormalization>                              
        <OutNormalization>'.$defaults["OutNormalization"].'</OutNormalization>                           
</Spellcheck>                                                                                                  
</soap:Body>                                                                                                   
</soap:Envelope>';                                                                                             

    // echo trim($xml);
    //xit;

    return trim($xml);
  }                   

  /**
   *   return an appropiate header for the curl class to make a soap request to Ankiro spellcheck
  */
  private function getSoapHeader()
  {
    $head=array();
    $head[]="Content-Type: text/xml;charset=UTF-8";
    $head[]="SOAPAction:http://www.ankiro.dk/AnkiroLanguageServices/Spellcheck/Spellcheck";
    return $head;
  }
}

/**
 * VERY simple class to parse response from Ankiro spellcheck
 * @xml; the xml to parse
 * @return; an array of terms
 */
class spellcheck_parser
{
  private $dom;
  public $term=array();
  public $error;

  public function  __construct($xml)
  {
    $this->dom=new DOMDocument();
    if( !$this->dom->loadXML($xml) )
      {
        //echo "FJEL OG MNAGLER";
        $this->error[]="SPELLCHECK_PARSER: could not load xml";
      }

    if( $this->error )
      return false;
    
    $this->parse();
  }

  private function parse()
  {
    // get all suggestions
    $nodeList = $this->dom->getElementsByTagName("Suggestion");
    foreach( $nodeList as $node )
      {
        $this->term[]=$this->getTerm($node);
      }
  }

  private function getTerm($node)
  {
    $term->_namespace="http://oss.dbc.dk/ns/openspell";
    $term->_value->suggestion->_value=xml_func::UTF8($node->getAttribute("word"));
    $term->_value->suggestion->_namespace="http://oss.dbc.dk/ns/openspell";
    $term->_value->weight->_value=$node->getAttribute("weight");
    $term->_value->weight->_namespace="http://oss.dbc.dk/ns/openspell";
    return $term;
  }
}

?>
