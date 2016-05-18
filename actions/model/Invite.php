<?php
include_once 'ModelBase.php';

class Invite extends ModelBase
{
	public $inviteAdd;
	public $inviteDel;
	
	public function checkValidity()
	{
		if(count($this->inviteAdd) > 0 || count($this->inviteDel))
		{
			return false;
		}
		return $this;
	}
}
?>