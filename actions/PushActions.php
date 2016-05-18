<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Push.php';

class PushActions extends ActionsBase implements ActionsInterface
{
	public $push;
	
	public function __construct()
	{
		$this->push = new Push();
	}
	
	public function preInsert($data)
	{
		$this->push->pushCreateDate = strtotime("now");
		unset($this->push->pushAction);
		return $this->DBWrapper($data);
	}
}
?>