<?php
class SpellRequest
{
	public $Word;//string
	public $Number;//integer
	public $language;//string
	public $filter;//string
}
class SpellResponse
{
	public $Term;//Term
}
class Term
{
	public $suggestion;//string
	public $weight;//string
}
$classmap=array("SpellRequest"=>"SpellRequest",
"SpellResponse"=>"SpellResponse",
"Term"=>"Term");
?>
