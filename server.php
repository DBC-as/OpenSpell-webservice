<?php
/**                                                                                
 *                                                                                 
 * This file is part of OpenLibrary.                                               
 * Copyright © 2009, Dansk Bibliotekscenter a/s,                                   
 * Tempovej 7-11, DK-2750 Ballerup, Denmark. CVR: 15149043                         
 *                                                                                 
 * OpenLibrary is free software: you can redistribute it and/or modify             
 * it under the terms of the GNU Affero General Public License as published by     
 * the Free Software Foundation, either version 3 of the License, or               
 * (at your option) any later version.                                             
 *                                                                                 
 * OpenLibrary is distributed in the hope that it will be useful,                  
 * but WITHOUT ANY WARRANTY; without even the implied warranty of                  
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                   
 * GNU Affero General Public License for more details.                             
 *                                                                                 
 * You should have received a copy of the GNU Affero General Public License        
 * along with OpenLibrary.  If not, see <http://www.gnu.org/licenses/>.            
*/                

/**
server for webservice to expose a simple version of Ankiro spellcheck. This script is 
based on php SOAP extension. 
*/

// curl class for handling remote calls to Ankiro spellcheck
require_once('ws_lib/curl_class.php');
// timer 
require_once('ws_lib/timer_class.php');
// verbose
require_once('ws_lib/verbose_class.php');
// ini-file
require_once("ws_lib/inifile_class.php");
// xml-functions
require_once("ws_lib/xml_func_class.php");
// required for class-mapping
require_once("spellService_classes.php");

//echo "TESTHEST";
$server=new openspell_server("openspell.ini");
$server->handle_request();

class openspell_server
{
  private $config;
  private $verbose;
  private $watch;

  public function  __construct($inifile)
  {
    // initialize config and verbose objects
    $this->config = new inifile($inifile);
    
    if( $this->config->error )
      {
	die("Cannot read " . $inifile." Error: ".$this->config->error );
      }

    $this->verbose=new verbose($this->config->get_value("logfile", "setup"),
			       $this->config->get_value("verbose", "setup")); 

    
    $this->watch = new stopwatch("", " ", "", "%s:%01.3f");
    $this->watch->start('spellservice');
  } 

  public function __destruct()
  {
    // stop the watch
    $this->watch->stop('spellservice');
    // and do the logging
    $this->verbose->log(TIMER, $this->watch->dump());
  }

  public function handle_request()
  {
    if( isset($_GET["HowRU"]) )
      {
	$this->HowRU();
	return;
      }
    elseif( isset($GLOBALS['HTTP_RAW_POST_DATA']) )
      { 	
	$this->soap_request(); 
	return;
      }       
    elseif( !empty($_SERVER['QUERY_STRING']) )
      {
        $response = $this->rest_request();
      }
    else // no valid request was made; generate an error

      {
	$this->send_error();
	return;
      }

  }

  private function HowRU()
  {
    // get HowRU section from ini-file
    $req = $this->config->get_section('HowRU');
    $request = new spellRequest();
    $request->word = $req['word'];
    $request->number = $req['number'];
    $request->language = $req['language'];

    $met = new methods();
    if( $response=$met->openSpell($request) )
      {
	if( !$response->error )
	  die('gr8');
      }
    else
      die("arrrgh: ".$met->error);    
  }

  private function soap_request()
  {    
    $params = array(  
		    "trace"=>true,
		    "classmap"=>$classmap
		      );
    
    $wsdlpath = $this->config->get_value("wsdl","setup");
    try{
      $server = new SoapServer($wsdlpath,$params); 
    }
    catch( SoapFault $exception ){
      // TODO log with verbose class
      $verbose->log(FATAL,$exception);
      exit;
    }   
    
    $server->setClass('methods');
    $server->handle();
  }

  private function rest_request()
  {
    // get the query
    $querystring =  $_SERVER['QUERY_STRING'];   
    // set the request
    $request = $this->map_url($querystring);
    // set default values
    $this->set_default($request);
    // get the response
    $met = new methods();
    $response = $met->openSpell($request);
    
    /*handle the response*/

    // set default outputType
    $type = "xml"; 
    if( !empty($request->outputType) )
	$type = strtolower($request->outputType);

    switch( $type )
      {
      case "xml":
       	header('Content-type:text/xml;charset=UTF-8');
	echo xml_func::object_to_xml($response);
	break;
      case "json":
	if( empty($request->callback) )
	  echo json_encode($response);
	else
	  echo $request->callback." && ".$request->callback."(".json_encode($response).")";
	break;
      default:
	$error = "Please give me correct outputType; XML og JSON";
	$this->send_error($error);
	break;	    
      }   

  }

  private function set_default(spellRequest $request)
  {
    if( empty($request->number) )
      $request->number=5;
    if( empty($request->language) )
      $request->language="danish";
    if( empty($request->filter) )
      $request->filter="asc";
  } 

  /**
   * Map given querystring to spellRequest-object
   * @param querystring; the querystring from request-url
   * @return spellRequest-object
   */
  private function map_url(&$querystring)
  {       
    if( empty($querystring) )
      return false;

    $request = new spellRequest();
    
    $parts=explode('&',$querystring);
    foreach( $parts as $part )
      {
	$val=explode('=',$part);

	if( $val[0] && $val[1] )
	switch( $val[0] )
	  {
	  case "word":
	    $request->word=$val[1];
	    break;
	  case "number":
	    $request->number=$val[1];
	    break;
	  case "filter":
	     $request->filter=$val[1];
	    break;
	  case "language":
	     $request->language=$val[1];
	    break;
	  case "outputType":
	     $request->outputType=$val[1];
	    break;
	  case "callback":
	     $request->callback=$val[1];
	    break;
	    }
      }
    return $request;
  }
 
 /**
   * Make a response with an error
   * @param message; The message to send as error
   * @return; echoes spellResponse-object as xml.
   */
  private function send_error($message=null)
  {
    // make a nice response to be polite
    $response=new spellResponse();
    // set default message
    if( !isset($message) )
      $message = "Please give me something to scan for like: ?word=hest";

    $response->error =xml_func::UTF8($message);
    // return message as xml
    header('Content-type:text/xml;charset=UTF-8');
    echo  xml_func::object_to_xml($response);   
  }

}

/**
 * Class containing spellcheck functions  
 */
class methods
{ 
  public $error;

  public function methods()
  {
    $this->error = null;
  }

  /*
    default settings for call to ankiro spellcheck
   */
  // TODO these settings would be better to get from some config file
  private $defaults=array("LCID"=>"1030",
			  "MaxPermutations"=>"2000",
			  "MinWeight"=>"0.4",
			  "SpellCheckOnlyIfNotExists"=>"false",
			  "InNormalization"=>"ENormalizationIndex",
			  "OutNormalization"=>"ENormalizationIndex",
			  "url"=>"http://fillmore.dbc.dk/spellcheck.asmx");
  
 
 
  /** 
   *   make the spellcheck.
   *   this method uses curl class to do the actual request to Ankiro spellcheck
   *
   *   params
   *   $request: the soap request mapped to SpellRequest class      
   *   @return: the soap response mapped to SpellResponse class
  */
  public function openSpell(spellRequest $request)
  {
    $curl=new curl();
    $curl->set_url($this->defaults["url"]);

    $curl->set_option(CURLOPT_HTTPHEADER, $this->getSoapHeader());
    $curl->set_option(CURLOPT_POST, 1);
    $curl->set_option(CURLOPT_POSTFIELDS,$this->getSoapBody($request));    

    $ret=$curl->get();

    $status = $curl->get_status();
    if( $status['error'] )
      {
	$this->error=$status['error'];
	return false;      
      }

    if( $status['http_code']!= 200 )
      {
	$this->error="openSpell::224:Error from curl class: http-code: ".$status['http_code'];
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
      return $parser->response;

    $this->error=$parser->error;
    return false;
  }

  /** 
   *   returns the xml for making a soap request to Ankiro spellcheck
   */
  private function getSoapBody($request=null)
  {
    $xml='
    <?xml version="1.0" encoding="utf-8"?> 
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"> 
<soap:Body> 
<Spellcheck xmlns="http://www.ankiro.dk/AnkiroLanguageServices/Spellcheck">
        <Word>'.$request->word.'</Word>
        <LCID>'.$this->defaults["LCID"].'</LCID>
        <ThesaurusFriendlyName>'.$request->language.'</ThesaurusFriendlyName>
        <MaxNumSuggestions>'.$request->number.'</MaxNumSuggestions>
        <MaxPermutations>'.$this->defaults["MaxPermutations"].'</MaxPermutations>
        <MinWeight>'.$this->defaults["MinWeight"].'</MinWeight>
        <SpellcheckOnlyIfNotExists>'.$this->defaults["SpellCheckOnlyIfNotExists"].'</SpellcheckOnlyIfNotExists>
        <InNormalization>'.$this->defaults["InNormalization"].'</InNormalization>
        <OutNormalization>'.$this->defaults["OutNormalization"].'</OutNormalization> 
</Spellcheck> 
</soap:Body> 
</soap:Envelope>';

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
 */
class spellcheck_parser
{
  private $dom;
  public $response;
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

    $this->response = new SpellResponse();

    $this->parse();
  }

  private function parse()
  {
    // get all suggestions
    $nodeList = $this->dom->getElementsByTagName("Suggestion");
    foreach( $nodeList as $node )
      {
	$this->response->term[]=$this->getTerm($node);
      }
     //  echo $this->dom->saveXML();
  }

  private function getTerm($node)
  {
    $term = new term();
    $term->suggestion=xml_func::UTF8($node->getAttribute("word"));
    $term->weight=$node->getAttribute("weight");
    return $term;    
  }
}


?>


