<?php
include_once 'ModelBase.php';
include_once 'ModelInterface.php';

class Token extends ModelBase implements ModelInterface
{
	public $tokenId;
	public $userId;
	public $tokenValue;
	public $tokenTimestamp;
	public $tokenOldValue;
	public $tokenOldTimestamp;
	public $tokenWasTaken;
	
	public function checkValidity()
	{
		if($this->tokenValue != null && $this->userId)
		{
			return $this;
		}
		return false;
	}
}
?>