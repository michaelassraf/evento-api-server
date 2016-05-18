<?php
include_once 'actions/NotificationActions.php';
include_once 'actions/RequestActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"callType":"Notification","since":0},"meta":{"token":{"tokenValue":"4a68e5061399c094ea6c0828d0428830","userId":13852058266871149},"statusCode":0}}';
$jsonData = $HTTP_RAW_POST_DATA;

$requestActionsObj = new RequestActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($requestActionsObj->request->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token))
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);

		if($data)
		{
			$tokenActionsObj->token->tokenValue = $data[0]["value"];
			$query = $couchbaseHandler->getUserNotifications($tokenActionsObj->token->userId, $requestActionsObj->request->since);
			if($query)
			{
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
				echo $wrapper->returnOK($query);
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
