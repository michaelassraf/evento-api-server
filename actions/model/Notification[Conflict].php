<?php
include_once 'ModelBase.php';

class Notification extends ModelBase
{
	public $notificationId;
	public $notificationUserId;
	public $notificationType; // 1 - post, 2 - user, 3 - event
	public $notificationDate;
	public $notificationConnectedId;
	public $notificationFullName;
	public $notificationFollow;
	public $notificationInvitation;
	public $notificationPost;
	
	public function checkValidity()
	{
	}
}
?>