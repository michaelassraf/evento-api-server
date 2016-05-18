<?php
class Response
{
	public $statusCode;
	public $statusMessage;
	public $token;
	
	public function __construct()
	{
		$this->token = null;
	}
	
	public function responseOK()
	{
		$this->statusCode = '10';
		$this->statusMessage='OK';
	}
	
	public function responseInvalidInput()
	{
		$this->statusCode = '1';
		$this->statusMessage='Invalid Input';
	}
	
	public function responseDataExist()
	{
		$this->statusCode = '2';
		$this->statusMessage='Record Exist';
	}
	
	public function responseInvalidToken()
	{
		$this->statusCode = '3';
		$this->statusMessage='Invalid Token';
	}
	
	public function responseDataNotExist()
	{
		$this->statusCode = '4';
		$this->statusMessage='Record Not Exist';
	}
	
	public function responseBreachAttempt()
	{
		$this->statusCode = '5';
		$this->statusMessage='Excpetion error';
	}
	
	public function responseWrongType()
	{
		$this->statusCode = '6';
		$this->statusMessage='Wrong file type';
	}
	
	public function responseInvalidQuery()
	{
		$this->statusCode = '7';
		$this->statusMessage='Not invited';
	}
	
	public function responseError($message)
	{
		$this->statusCode = '9';
		$this->statusMessage=$message;
	}
}
?>