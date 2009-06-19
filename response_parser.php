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

require_once('spellService_classes.php');

/**
   VERY simple class to parse response from Ankiro spellcheck
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
    $term->suggestion=$node->getAttribute("word");
    $term->weight=$node->getAttribute("weight");
    return $term;    
  }
}
  
?>
