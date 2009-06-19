<?php
/**
server for webservice to expose a simple version of Ankiro spellcheck. This script is 
based on php SOAP extension. 
*/

// TODO : handle rest

// disable caching of web service definitions while testing.
// remove this line when in production
ini_set('soap.wsdl_cache_enabled',0);

// curl class for handling call to Ankiro spellcheck
require_once('ws_lib/curl_class.php');
// xml parser for mapping response from Ankiro to classmap
require_once('response_parser.php');
// timer 
require_once('ws_lib/timer_class.php');
// verbose
require_once('ws_lib/verbose_class.php');
// ini-file
require_once("ws_lib/inifile_class.php");
// xml-functions
require_once("ws_lib/xml_func_class.php");


// get ini-file
define(INIFILE, "openspell.ini");
try{  $config = new inifile(INIFILE);
}
catch( exception $e )
{
  die("Cannot read " . INIFILE . " Exception: " . $e);
}

// prepare log-object
$verbose = new verbose($config->get_value("logfile", "setup"), 
                       $config->get_value("verbose", "setup"));

// set constants for soap request
define(WSDL, $config->get_value("wsdl", "setup"));
define(CLASSES,$config->get_value("classes","setup"));

// include for soap-classes
require_once(CLASSES); 

// check if request is made directly to servers HowRU-method ( testing )
if( isset($_GET['HowRU']) )
  {
    // make a request-object from config-file
    $req=$config->get_section("HowRU");
    $request = new SpellRequest();
    $request->Word = $req['word'];
    $request->Number = $req['number'];
    $request->language = $req['language'];

    //var_dump($request);
    $met = new methods();
    $response=$met->SpellCheck($request);
    // var_dump($response);
    if( !$response->error )
      die('gr8');
    else
      die($response->error);
    
  }


// time the operation
$watch = new stopwatch("", " ", "", "%s:%01.3f");
$watch->start('spellservice');

// handle soap-request (post)
if( isset($HTTP_RAW_POST_DATA) )
  {
    $params = array(  
		    "trace"=>true,
		    "classmap"=>$classmap
		      );
    
    $wsdlpath = WSDL;
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
else // postdata is not set, so this is probably a get-request
  {
    $request = new SpellRequest();
    if( isset($_GET['word']) )
      $request->Word=$_GET['word'];
    else // if a word is not set spellchecking is pointless
      die( "Spellcheck needs a word to check" );
    // rest of vars are given default values if not set
    if( isset($_GET['number']) )
      $request->Number=$_GET['number'];
    else
      $request->Number=5;
    if( isset($_GET['language']) )
      $request->language=$_GET['language'];
    else
      $request->language="danish";
    if( isset($_GET['filter']) )
      $request->filter=$_GET['filter']; 
    else
      $request->filter="asc";
       

    // make an object to handle request
    $handler=new methods();
    $response=$handler->openSpell($request);

    if( isset($_GET['outputtype']) )
      $type=$_GET['outputtype'];
    else
      $type="xml";
    
    switch($type)
      {
      case "xml" :
	echo xml_func::object_to_xml($response);
	break;
      case "json":
	echo json_encode($response);
	break;
      default:
        die();// this can not happen
      }
    
    $watch->stop('spellservice');
    // and do the logging
    $verbose->log(TIMER, $watch->dump());
  }

// handle rest-request (get)

/* initialize and return a SpellRequest-object from url-parameters */
function get_request()
{
  
  return null;
}

class methods
{ 
  public static $error;

  public function methods()
  {
    self::$error = null;
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
      wrapper for functioncall to Ankiro spellcheck
      params
      $request: the soap request mapped to SpellRequest class      
      @return: the soap response mapped to SpellResponse class
   */
  public function openSpell(SpellRequest $request)
  {
    global $verbose;
    if( ! ($ret = $this->AnkiroSpellCheck($request)) ) 
      {
	// TODO log error with verbose
	$verbose->log(WARNING,self::$error);
	return new SoapFault("server",self::$error);
      }

    return $ret;
  }  

  /** 
      make the spellcheck.
      this method uses curl class to do the actual request to Ankiro spellcheck

      params
      $request: the soap request mapped to SpellRequest class
      
      @return: the soap response mapped to SpellResponse class
  */
  private function AnkiroSpellCheck($request)
  {
    $curl=new curl();
    $curl->set_url($this->defaults["url"]);

    $curl->set_option(CURLOPT_HTTPHEADER, $this->getSoapHeader());
    $curl->set_option(CURLOPT_POST, 1);
    $curl->set_option(CURLOPT_POSTFIELDS,$this->getSoapBody($request));
      
    $ret=$curl->get();
   
    // check for errors
    $status = $curl->get_status();
    if( $status['error'] )
      {
	self::$error=$status['error'];
	return false;      
      }
    return $this->ParseResult($ret);
  } 

  /**
     This method uses response_parser class to parse the xml from Ankiro spellcheck. 
     response_parser class generates an object of SpellResponse type.

     params:
     $xml: the soap response from Ankiro spellcheck
     return: response mapped to SpellResponse class
   */
  private function ParseResult(&$xml)
  {
    $parser = new spellcheck_parser($xml);

    // check for errors
    if( $parser )
      return $parser->response;

    self::$error=$parser->error;
    return false;
  }

  /** 
      returns the xml for making a soap request to Ankiro spellcheck
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
      return an appropiate header for the curl class to make a soap request to Ankiro spellcheck
  */
  private function getSoapHeader()
  {
    $head=array();
    $head[]="Content-Type: text/xml;charset=UTF-8";
    $head[]="SOAPAction:http://www.ankiro.dk/AnkiroLanguageServices/Spellcheck/Spellcheck";
    return $head;      
  }
}

?>
