<?php
include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/EventActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/GuestActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"eventStartDate":0,"eventId":13834125573170126,"eventEndDate":0},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a964","userId":13810055104469089}}}';
//$jsonData = '{"data":{"eventId":13858161753174578},"meta":{"token":{"tokenValue":"8db412c8f35a530a139a7649634061f3","userId":13858151849825562}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$eventActionsObj = new EventActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($eventActionsObj->event->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token))
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);
		
		if($data)
		{
			$tokenActionsObj->token->tokenValue = $data[0]["value"];
			$check = $couchbaseHandler->validateGuestInEvent($tokenActionsObj->token->userId, $eventActionsObj->event->eventId);
			if($check)
			{
				$event = $couchbaseHandler->getEvent($eventActionsObj->event->eventId);
				//var_dump($event);
				if($event)
				{
					$eventActionsObj->event = $event;
					$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
					echo $wrapper->returnOK($eventActionsObj->event);
				}
				else
				{
					echo $wrapper->returnDataNotExist();
				}
			}
			else
			{
				echo $wrapper->returnInvalidQuery();	
			}
		}
		else
		{
			echo $wrapper->returnInvalidToken();
		}
	}
	else
	{
		echo $wrapper->returnInvalidInput();
	}
}
else
{
	echo $wrapper->returnInvalidInput();
}