<?php

require_once("ws_lib/curl_class.php");
require_once("ws_lib/inifile_class.php");
require_once("ws_lib/xml_func_class.php");
/**                                                                                
 *                                                                                 
 * This file is part of Open Library System.                                               
 * Copyright © 2009, Dansk Bibliotekscenter a/s,                                   
 * Tempovej 7-11, DK-2750 Ballerup, Denmark. CVR: 15149043                         
 *                                                                                 
 * Open Library System is free software: you can redistribute it and/or modify             
 * it under the terms of the GNU Affero General Public License as published by     
 * the Free Software Foundation, either version 3 of the License, or               
 * (at your option) any later version.                                             
 *                                                                                 
 * Open Library System is distributed in the hope that it will be useful,                  
 * but WITHOUT ANY WARRANTY; without even the implied warranty of                  
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                   
 * GNU Affero General Public License for more details.                             
 *                                                                                 
 * You should have received a copy of the GNU Affero General Public License        
 * along with Open Library System.  If not, see <http://www.gnu.org/licenses/>.            
*/                

// diable caching of wsdl while developing
ini_set('soap.wsdl_cache_enabled',0);
if (! isset($_POST['submit']))
  {
    echo getForm();
    exit();
  }

// prepare the request from fields on the form
$request=array("word"=>$_POST['word'],
	       "number"=>$_POST['number'],
	       "language"=>$_POST['language'],
	       "filter"=>"");


$curl=new curl();   

$config=new inifile("openspell.ini");
$url = $config->get_value("ws_url","setup");

//echo $url;

$curl->set_url($url);                                     

$curl->set_option(CURLOPT_HTTPHEADER, getSoapHeader());
$curl->set_option(CURLOPT_POST, 1);                           
$curl->set_option(CURLOPT_POSTFIELDS,getSoapBody($request));    

$ret=$curl->get();

$parser=new spellcheck_parser($ret);

$results=$parser->terms;
// print the form with fields set from request
echo getForm($request);

echo "Resultat:<br/>";
if( is_array($results) )
  {
    foreach( $results as $key=>$val )
      echo $val["suggestion"].":".$val["weight"]."<br/>\n";
  }
else
  echo "Stavekontrol har ikke nogen forslag";

/** print a form. if values are set from last request; set fields according to request */
function getForm($request=null)
{
  return 
'<form  method="post" name="phpform">
   Ord:<input type="text" value="'.(($request["word"])?$request["word"] : 'skriv et ord' ).'" name="word" />
   antal:<input type="text" value="'.(($request["number"])?$request["number"] : '8' ).'" name="number" style="width:40px;margin-bottom:2px"/>
   <br/>
   sprog:<select name="language">
   <option value="danish">dansk</option>
   </select>
   filter:<select name="filter">
   <option value="asc">asc</option>
   </select>
   <input type="submit" value="Find staveforslag" name="submit"/> 
 </form >'."\n";
}

function getSoapBody($params)
{

$ret='

<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema"                         
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:types="http://oss.dbc.dk/ns/openspell">              
<soap:Body>                                                          
<types:spellRequest>
        <types:word>'.$params["word"].'</types:word>
        <types:number>'.$params["number"].'</types:number>
        <types:language>'.$params["language"].'</types:language>
        <types:filter>'.$params["filter"].'</types:filter>
</types:spellRequest>                                                                                                  
</soap:Body>                                                                                                   
</soap:Envelope>';

return $ret;
}

function getSoapHeader()
{
 $head=array();
    $head[]="Content-Type: text/xml;charset=UTF-8";
    $head[]="SOAPAction:http://www.ankiro.dk/AnkiroLanguageServices/Spellcheck/Spellcheck";
    return $head;
}

/**
 * VERY simple class to parse response from Ankiro spellcheck
 * @xml; the xml to parse
 * @return; an array of terms
 */
class spellcheck_parser
{
  private $dom;
  public $terms=array();
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
    $nodeList = $this->dom->getElementsByTagName("term");
    foreach( $nodeList as $node )
      {
	$term=array();
	$nod = $node->getElementsByTagName("suggestion");
	$term["suggestion"]=$nod->item(0)->nodeValue;
	$nod = $node->getElementsByTagName("weight");
	$term["weight"]=$nod->item(0)->nodeValue;
	
	$this->terms[]=$term;
       
      }
  }

}
?>

