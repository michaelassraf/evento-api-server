<?php
include_once 'actions/FollowActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"followConnectedId":13810055104469089, "followAction":3},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$followActionsObj = new FollowActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($followActionsObj->follow->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token))
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);

		if($data)
		{
			$tokenActionsObj->token->tokenValue = $data[0]["value"];
			$usersArr = array();
			$followers = $couchbaseHandler->getFollowersObjects($followActionsObj->follow->followConnectedId);
			foreach ($followers as $follow)
			{
				$user = $couchbaseHandler->getUser($follow["followUserId"]);
				if($user)
				{
					array_push($usersArr, $user);
				}
			}
			if(count($usersArr))
			{
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
				echo $wrapper->returnOK($usersArr);
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