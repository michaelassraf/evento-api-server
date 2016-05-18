<?php
include_once 'actions/PushActions.php';
include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"pushToken":"APA91bFLlX6xeIcnAsTxwC99zoO15Y5f_ATz0pccMIK6yDcfal", "pushAction":2},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$pushActionsObj = new PushActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($pushActionsObj->push->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token))
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);

		if($data)
		{
			$tokenActionsObj->token->tokenValue = $data[0]["value"];
			if($pushActionsObj->push->pushAction == 1) // Insert
			{
				$pushActionsObj->push->pushUserId = $tokenActionsObj->token->userId;
				$pushActionsObj->push = $couchbaseHandler->getPushToken($pushActionsObj->push, $_SERVER['HTTP_USER_AGENT']);
				if($pushActionsObj->push->status == "exist")
				{
					unset($pushActionsObj->push->status);
					$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
					echo $wrapper->returnOK($pushActionsObj->push);
				}
				else
				{
					if($pushActionsObj->push->status == "new")
					{
						$pushActionsObj->push->pushId = $pushActionsObj->generate();
					}
					unset($pushActionsObj->push->status);
					$couchbaseHandler->saveDocument($pushActionsObj->push->pushId, $pushActionsObj->preInsert($pushActionsObj->push));
					$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
					echo $wrapper->returnOK($pushActionsObj->push);
				}
			}
			else // Delete
			{
				$push = $couchbaseHandler->getPush($tokenActionsObj->token->userId);
				if($push)
				{
					$couchbaseHandler->deleteDocument($push["pushId"]);
					$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
					echo $wrapper->returnOK(null);
				}
				else
				{
					echo $wrapper->returnDataNotExist();
				}
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
