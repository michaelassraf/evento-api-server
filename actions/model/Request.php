<?php
include_once 'ModelBase.php';

class Request extends ModelBase
{ 
	public $callType;
	public $since;
	
	public function checkValidity()
	{
		if(intval($this->since) || $this->since == 0)
		{
			if($this->since == 0)
			{
				$this->since = "0";
			}
			return $this;
		}
		return false;
	}
}