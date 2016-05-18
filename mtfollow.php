<?php
include_once 'actions/FollowActions.php';
include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"followConnectedId":13810055104469090,"followAction":1},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
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
			if($followActionsObj->follow->followConnectedId != $tokenActionsObj->token->userId) // Cant follow yourself
			{
				$tokenActionsObj->token->tokenValue = $data[0]["value"];
				$follow = $couchbaseHandler->getFollowByConnectedId($tokenActionsObj->token->userId, $followActionsObj->follow->followConnectedId);
				$followActionsObj->follow->followUserId = $tokenActionsObj->token->userId;
				if($followActionsObj->follow->followAction == 1) // Follow
				{
					if($follow == null)
					{
						$followActionsObj->follow->followId = $followActionsObj->generate();
						$couchbaseHandler->saveDocument($followActionsObj->follow->followId, $followActionsObj->preInsert($followActionsObj->follow));
						$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
						echo $wrapper->returnOK($followActionsObj->follow);
						$couchbaseHandler->deployNotifications($followActionsObj->follow->followId);
					}
					else // Already following
					{
						echo $wrapper->returnDataExist();
					}
				}
				else // Unfollow
				{
					if($follow != null)
					{
						$couchbaseHandler->deleteDocument($follow["followId"]);
						$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
						echo $wrapper->returnOK(null);
					}
					else // Not existing
					{
						echo $wrapper->returnDataNotExist();
					}
				}
			}
			else
			{
				echo $wrapper->returnInvalidInput();
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