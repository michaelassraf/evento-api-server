<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/User.php';

class UserActions extends ActionsBase implements ActionsInterface
{
	public $user;
	
	public function __construct()
	{
		$this->user = new User();
	}
	
	public function preInsert($data)
	{
		unset($data->actionType);
		return $this->DBWrapper($data);
	}
	
	public function preReturn($data)
	{
		unset($data->actionType);
		unset($data->userPassword);
		return $data;
	}
}
?>