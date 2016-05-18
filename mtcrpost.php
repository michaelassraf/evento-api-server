<?php

include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/PostActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/GuestActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"postEventId":13835803851862313},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$postActionsObj = new PostActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($postActionsObj->post->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token))
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);
		
		if($data)
		{
			$check = $couchbaseHandler->validateGuestInEvent($tokenActionsObj->token->userId, $postActionsObj->post->postEventId);
			$eventName = $couchbaseHandler->getEventName($postActionsObj->post->postEventId);
			if($check && $eventName)
			{
				$postActionsObj->post->postId = $postActionsObj->generate();
				$postActionsObj->post->postPosterId = $tokenActionsObj->token->userId;
				$postActionsObj->post->postEventName = $eventName;
				$couchbaseHandler->saveDocument($postActionsObj->post->postId, $postActionsObj->preInsert($postActionsObj->post));
				
				$tokenActionsObj->token->tokenValue = $data[0]["value"];
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
				echo $wrapper->returnOK($postActionsObj->post);
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
