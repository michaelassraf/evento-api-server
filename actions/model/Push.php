<?php
include_once 'ModelBase.php';

class Push extends ModelBase
{ 
	public $pushId;
	public $pushUserId;
	public $pushToken;
	public $pushType;
	public $pushOS;
	public $pushDevice;
	public $pushCreateDate;
	public $pushAction; // 1 - push, 2 - cancel push
	
	public function checkValidity()
	{
		if(isset($this->pushToken)
		&& ($this->pushAction == 1 || $this->pushAction == 2))
		{
			return $this;
		}
		return false;
	}
}
?>