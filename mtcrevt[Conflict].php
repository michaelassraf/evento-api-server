<?php
define('!@@!CH_Includer@!!@',true);
include_once 'includes/utils.php';

include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/EventActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/GuestActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"eventDescription":"sdasda","eventType":"BarMitvah","eventPicture":"sdasda","eventName":"chiuv","eventOrganizer":13810055104469089,"eventId":13835803851862313,"eventStartDate":238182,"eventEndDate":2381822, "eventUsers":[{"userId":"13810055104469089","userFirstName":"Roi","userLastName":"Cohen","userEmail":"rcohenk@gmail.com","userBirthday":571104000,"userSex":"Man","userSexCode":1,"userDevice":0,"userJoinDate":1379961412,"userMazelPoints":null,"userProfilePicture":{"itemId":"13835958364374366","itemUrl":"13810055104469089_13835958364374366.jpg","itemType":"image\/jpeg; charset=binary","connectedId":"13810055104469089","itemCreateDate":1383595836}},{"userId": "13810055104469090"}]},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
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
			if($eventActionsObj->event->eventOrganizer == $tokenActionsObj->token->userId)
			{  
				$eventActionsObj->event->eventId = $eventActionsObj->generate();
				// Save user as guest
				$guestActionsObj = new GuestActions();
				$guestActionsObj->guest->guestId = $guestActionsObj->generate(); 
				$guestActionsObj->guest->guestUserId = $tokenActionsObj->token->userId;
				$guestActionsObj->guest->guestEventId = $eventActionsObj->event->eventId;
				$couchbaseHandler->saveDocument($guestActionsObj->guest->guestId, $guestActionsObj->preInsert($guestActionsObj->guest));
				// Save event guests
				if(isset($eventActionsObj->event->eventUsers) && count((array)$eventActionsObj->event->eventUsers) > 0)
				{
					foreach ($eventActionsObj->event->eventUsers as $user)
					{
						if(isset($user->userId) && $user->userId != $tokenActionsObj->token->userId)
						{
							if($couchbaseHandler->checkifUserExistById($user->userId))
							{
								$guestActionsObj = new GuestActions();
								$guestActionsObj->guest->guestId = $guestActionsObj->generate(); 
								$guestActionsObj->guest->guestUserId = $user->userId;
								$guestActionsObj->guest->guestEventId = $eventActionsObj->event->eventId;
								$couchbaseHandler->saveDocument($guestActionsObj->guest->guestId, $guestActionsObj->preInsert($guestActionsObj->guest));
							}
						}
					}
				}
				// Save event
				$couchbaseHandler->saveDocument($eventActionsObj->event->eventId, $eventActionsObj->preInsert($eventActionsObj->event));
				// Return data
				$tokenActionsObj->token->tokenValue = $data[0]["value"];
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
				echo $wrapper->returnOK($eventActionsObj->event);
				// Send notification
				$couchbaseHandler->deployNotifications($eventActionsObj->event->eventId);
			}
			else
			{
				echo $wrapper->returnBreachAttempt();
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
?>