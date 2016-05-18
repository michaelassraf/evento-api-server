<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Guest.php';

class GuestActions extends ActionsBase implements ActionsInterface
{
	public $guest;
	
	public function __construct()
	{
		$this->guest = new Guest();
	}
	
	public function preInsert($data)
	{
		return $this->DBWrapper($data);
	}
}
?>