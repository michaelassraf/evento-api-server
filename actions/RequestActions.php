<?php
include_once 'ActionsBase.php';
include_once 'model/Request.php';

class RequestActions extends ActionsBase
{
	public $request;
	
	public function __construct()
	{
		$this->request = new Request;
	}
}
?>