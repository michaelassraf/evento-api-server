<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Event.php';

class EventActions extends ActionsBase implements ActionsInterface
{
	public $event;
	
	public function __construct()
	{
		$this->event = new Event();
	}
	
	public function preInsert($data)
	{
		$this->event->eventPicture = null;
		$this->event->eventUsers = null;
		$this->event->eventCreateDate = strtotime("now");
		return $this->DBWrapper($data);
	}
}
?>