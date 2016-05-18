<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Follow.php';

class FollowActions extends ActionsBase implements ActionsInterface
{
	public $follow;
	
	public function __construct()
	{
		$this->follow = new Follow();
	}
	
	public function preInsert($data)
	{
		$this->follow->followCreateDate = strtotime("now");
		return $this->DBWrapper($data);
	}
}
?>