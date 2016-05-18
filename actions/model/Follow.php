<?php
include_once 'ModelBase.php';

class Follow extends ModelBase
{
	public $followId;
	public $followUserId;
	public $followConnectedId;
	public $followCreateDate;
	public $followAction; // 1 - follow, 2 - unfollow. 3 - query
	
	public function checkValidity()
	{
		if(isset($this->followConnectedId) && isset($this->followAction) 
		&& ($this->followAction == 1 || $this->followAction == 2 || $this->followAction == 3))
		{
			return $this;
		}
		return false;
	}
}