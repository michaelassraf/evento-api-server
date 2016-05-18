<?php

include_once 'model/Response.php';

class Wrapper
{
	public $data;
	public $meta;
	
	public function __construct()
	{
		$this->data = null;
		$this->meta = new Response();
	}
	
	public function returnOK($dataObj)
	{
		$this->data = $dataObj;
		$this->meta->responseOK();
		return json_encode($this); 
	}
	
	public function returnInvalidInput()
	{
		$this->meta->responseInvalidInput();
		return json_encode($this);
	}
	
	public function returnDataExist()
	{
		$this->meta->responseDataExist();
		return json_encode($this); 
	}
	
	public function returnInvalidToken()
	{
		$this->meta->responseInvalidToken();
		return json_encode($this);
	}
	
	public function returnDataNotExist()
	{
		$this->meta->responseDataNotExist();
		return json_encode($this); 
	}
	
	public function returnBreachAttempt()
	{
		$this->meta->responseBreachAttempt();
		return json_encode($this); 
	}
	
	public function returnError($error)
	{
		$this->meta->responseError($error);
		return json_encode($this); 
	}
	
	public function returnWrongType()
	{
		$this->meta->responseWrongType();
		return json_encode($this); 
	}
	
	public function returnInvalidQuery()
	{
		$this->meta->responseInvalidQuery();
		return json_encode($this); 
	}
	
	public function setReturnToken($token)
	{
		$this->meta->token = $token;
	}
}