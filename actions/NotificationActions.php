<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Notification.php';

class NotificationActions extends ActionsBase implements ActionsInterface
{
	public $notification;
	
	public function __construct()
	{
		$this->notification = new Notification();
	}
	
	public function preInsert($data)
	{
		$this->notification->notificationDate = strtotime("now");
		return $this->DBWrapper($data);
	}
}
?>