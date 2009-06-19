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

// operations and parameters are defined in the wsdl( Web Service Definition Language ) file.
// set path to wsdl. 
$wsdlpath="openspell.wsdl";

$params=array("trace"=>1);
try
{
  // create the client based on web service definition
  // you can set additional parameters for the soap-client. see http://dk2.php.net/manual/en/class.soapclient.php.
  // if you eg. set trace=>true you can see the soap requests and responses
  $client=new SoapClient($wsdlpath,$params);
  // make the request and collect the response
  $response=$client->openSpell($request);  

  // testing
  //  echo $client->__getLastResponse();
  //  echo $client->__getLastRequest();
}
catch( SoapFault $exception )
{
  // something went wrong. print the soapfault and exit
  echo "SOAP Fault:".$exception->faultcode." Message: ".$exception->faultstring."\n";
  exit();
}

// print the form with fields set from request
echo getForm($request);

// handle the response
$results = $response->term;

// print the result
echo "Resultat:<br/>";
if( is_array($results) )
  {
    foreach( $results as $obj )
      echo $obj->suggestion.":".$obj->weight."<br/>\n";
  }
else if(!empty($results) && ($results->suggestion && $results->weight) )
  {    
    echo $results->suggestion.":".$results->weight."<br/>\n";
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
?>

