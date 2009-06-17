<?php
require_once('SpellService_classes.php');

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
	$this->response->Term[]=$this->getTerm($node);
      }
     //  echo $this->dom->saveXML();
  }

  private function getTerm($node)
  {
    $term = new Term();
    $term->suggestion=$node->getAttribute("word");
    $term->weight=$node->getAttribute("weight");
    return $term;    
  }
}
  
?>