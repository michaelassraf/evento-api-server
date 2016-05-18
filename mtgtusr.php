<?php
include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"userId":13810055104469089},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$userActionsObj = new UserActions();
$userActionsObj->user->actionType = "query";
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($userActionsObj->user->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token))
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);

		if($data)
		{
			$tokenActionsObj->token->tokenValue = $data[0]["value"];
			$user = $couchbaseHandler->getUser($userActionsObj->user->userId);
			if($user)
			{
				$userActionsObj->user = $user;
					
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
				echo $wrapper->returnOK($userActionsObj->preReturn($userActionsObj->user));
			}
			else
			{
				echo $wrapper->returnDataNotExist();
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