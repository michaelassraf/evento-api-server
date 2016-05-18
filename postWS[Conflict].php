<?php
include_once 'actions/PushActions.php';
include_once 'actions/NotificationActions.php';
include_once 'controllers/couchbaseMethods.php';
include_once 'includes/apn.php';
include_once 'includes/gcm.php';
require_once 'includes/KLogger.php';
include_once 'actions/Wrapper.php';

$connectedId = "13864541196711141";
//$connectedId = json_decode($HTTP_RAW_POST_DATA);
$couchbaseHandler = new couchbaseMethods();
$obj = json_decode($couchbaseHandler->getDocument($connectedId));

$wrapper = new Wrapper();
$wrapper->setReturnToken(null);
$session = strtotime("now");
$log = new KLogger ( "log.txt" , KLogger::DEBUG );
$log->LogInfo($session . " Starting actions on " . $connectedId);
$log->LogInfo($session . " Header " . $HTTP_RAW_POST_DATA);

$type = check_object($obj);
if($type == 1) // Post added notification
{
	$log->LogInfo($session . " Post object");
	
	$guests = $couchbaseHandler->getGuests($obj->post->postEventId, false);
	$query = json_decode($couchbaseHandler->getDocument($obj->post->postPosterId));
	
	$full_name = $query->user->userFirstName . " " . $query->user->userLastName;
	
	$tempPost = $obj->post;
	$tempPost->postContent = $couchbaseHandler->getLastItem($obj->post->postId, true);
	$tempPost->postPoster = $couchbaseHandler->getUser($obj->post->postPosterId);
	
	foreach($guests as $item) // Get all guest id's and push notifications
	{
		$pushItems = $couchbaseHandler->getPushObjects($item);
		
		$notificationActionsObj = new NotificationActions();
		$notificationActionsObj->notification->notificationId = $notificationActionsObj->generate();
		$notificationActionsObj->notification->notificationConnectedId = $connectedId;
		$notificationActionsObj->notification->notificationUserId = $item;
		$notificationActionsObj->notification->notificationType = $type;
		$notificationActionsObj->notification->notificationFullName = $full_name;
		if($tempPost)
		{
			$notificationActionsObj->notification->notificationPost = $tempPost;
		} 
		$couchbaseHandler->saveDocument($notificationActionsObj->notification->notificationId,
		$notificationActionsObj->preInsert($notificationActionsObj->notification));
		
		foreach ($pushItems as $push) // Push notifications
		{
			$log->LogInfo($session . " Sending push to " . $push["pushUserId"]);
			sendNotifications($push, $notificationActionsObj->notification, $session);
		}
	}
}
elseif ($type == 2) // User follow notification
{
	$log->LogInfo($session . " User object");
	$query = json_decode($couchbaseHandler->getDocument($obj->follow->followUserId));
	$full_name = $query->user->userFirstName . " " . $query->user->userLastName;
	$pushItems = $couchbaseHandler->getPushObjects($obj->follow->followConnectedId);
	
	$notificationActionsObj = new NotificationActions();
	$notificationActionsObj->notification->notificationId = $notificationActionsObj->generate();
	$notificationActionsObj->notification->notificationConnectedId = $obj->follow->followConnectedId;
	$notificationActionsObj->notification->notificationUserId = $obj->follow->followUserId;
	$notificationActionsObj->notification->notificationType = $type;
	$notificationActionsObj->notification->notificationFullName = $full_name;
	$notificationActionsObj->notification->notificationFollow = $couchbaseHandler->getUser($obj->follow->followUserId);
	var_dump($notificationActionsObj->notification->notificationFollow);
	if(!($notificationActionsObj->notification->notificationFollow))
	{
		$notificationActionsObj->notification->notificationFollow = null;
	}
	$couchbaseHandler->saveDocument($notificationActionsObj->notification->notificationId,
	$notificationActionsObj->preInsert($notificationActionsObj->notification));

	foreach ($pushItems as $push) // Push notifications
	{
		$log->LogInfo($session . " Sending push to " . $push["pushUserId"]);
		sendNotifications($push, $notificationActionsObj->notification, $session);
	}
}
elseif ($type == 3) // Event invites notification
{
	$log->LogInfo($session . " Event object");
	
	$log->LogInfo($session . " Going to sleep");
	sleep(20);
	$guests = $couchbaseHandler->getGuests($obj->event->eventId, true);
	$query = json_decode($couchbaseHandler->getDocument($obj->event->eventOrganizer));
	$full_name = $query->user->userFirstName . " " . $query->user->userLastName;
	
	$log->LogInfo($session . " guests " . json_encode($guests));
	
	$tempEvent = $obj->event;
	$tempEvent->eventPicture = $couchbaseHandler->getLastItem($obj->event->eventId, true);
	if(!($tempEvent->eventPicture))
	{
		$tempEvent->eventPicture = null;
	}
	
	$log->LogInfo($session . " event " . json_encode($tempEvent));
	
	foreach($guests as $item) // Get all guest id's and push notifications
	{
		$pushItems = $couchbaseHandler->getPushObjects($item);
		
		$notificationActionsObj = new NotificationActions();
		$notificationActionsObj->notification->notificationId = $notificationActionsObj->generate();
		$notificationActionsObj->notification->notificationConnectedId = $connectedId;
		$notificationActionsObj->notification->notificationUserId = $item;
		$notificationActionsObj->notification->notificationType = $type;
		$notificationActionsObj->notification->notificationFullName = $full_name;
		if($tempEvent)
		{
			$notificationActionsObj->notification->notificationInvitation = $tempEvent;
		}
		$couchbaseHandler->saveDocument($notificationActionsObj->notification->notificationId,
		$notificationActionsObj->preInsert($notificationActionsObj->notification));
		
		foreach ($pushItems as $push) // Push notifications
		{
			$log->LogInfo($session . " Sending push to " . $push["pushUserId"]);
			sendNotifications($push, $notificationActionsObj->notification, $session);
		}
	}
}

function sendNotifications($arr, $notification, $session)
{
	$wrapper = new Wrapper();
	$log2 = new KLogger ( "push.txt" , KLogger::DEBUG );
	if(count($arr))
	{
		$gcm = new gcm();
		$apn = new apn();
		if($arr["pushType"] == 1)
		{
			$wrapped_object = $wrapper->returnOK($notification);
			$log2->LogInfo($session . " GCM");
			$log2->LogInfo($session . " ". $wrapped_object . " is the sent object and the token is " . $arr["pushToken"]);
			$gcm->sendMessage($arr["pushToken"], $wrapped_object);
		}
		elseif($arr["pushType"] == 2)
		{
			$wrapped_object = $wrapper->returnOK($notification);
			$log2->LogInfo($session . " APN");
			$log2->LogInfo($session . " " .$wrapped_object . " is the sent object and the token is " . $arr["pushToken"]);
			$apn->sendMessage($arr["pushToken"], $notification->notificationFullName,
			$notification->notificationType);
		}	
	}
}

function check_object($object)
{
	if(isset($object->post))
	{
		return 1;
	}
	elseif(isset($object->follow))
	{
		return 2;
	}
	elseif(isset($object->event))
	{
		return 3;
	}
	else
	{
		return false;
	}
}