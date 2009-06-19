<?php
class spellRequest
{
	public $word;//string
	public $number;//integer
	public $language;//string
	public $filter;//string
}
class spellResponse
{
	public $term;//term
}
class term
{
	public $suggestion;//string
	public $weight;//string
}
$classmap=array("spellRequest"=>"spellRequest",
"spellResponse"=>"spellResponse",
"term"=>"term");
?>
