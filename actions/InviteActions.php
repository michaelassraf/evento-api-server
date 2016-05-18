<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Invite.php';

class InviteActions extends ActionsBase
{
	public $invite;
	
	public function __construct()
	{
		$this->invite = new Invite();
	}
}
?>