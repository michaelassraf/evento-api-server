<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Token.php';

class TokenActions extends ActionsBase implements ActionsInterface
{
	public $token;
	
	public function __construct()
	{
		$this->token = new Token();
		$this->token->tokenId = $this->generate();
		$this->token->tokenTimestamp = strtotime("now");
		$this->token->tokenValue = $this->generateToken();
	}
	
	public function preInsert($data)
	{
		return $this->DBWrapper($data);
	}
	
	public function preReturn($data)
	{
		unset($this->token->tokenOldValue);
		unset($this->token->tokenOldTimestamp);
		unset($this->token->tokenId);
		unset($this->token->tokenTimestamp);
		unset($this->token->tokenWasTaken);
		return $this->token;
	}
	
	public function generateToken()
	{
    	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$randomString = '';
    	for ($i = 0; $i < 10; $i++) 
    	{
        	$randomString .= $characters[rand(0, strlen($characters) - 1)];
    	}
   		return md5($randomString.microtime(true));
	}
}
?>