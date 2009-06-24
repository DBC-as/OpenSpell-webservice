<?php
class spellRequest
{
	public $word;//string
	public $number;//integer
	public $language;//string
	public $filter;//string
	public $outputType;//output
	public $callback;//string
}
class spellResponse
{
	public $term;//term
	public $error;//string
}
class term
{
	public $suggestion;//string
	public $weight;//string
}
class output
{
	public $output;//string
}
$classmap=array("spellRequest"=>"spellRequest",
"spellResponse"=>"spellResponse",
"term"=>"term",
"output"=>"output");
?>
