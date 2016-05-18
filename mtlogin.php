<?php
define('!@@!CH_Includer@!!@',true);
include_once 'includes/utils.php';

include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"userSex":"Man","userEmail":"rcohenk@gmail.com","userFirstName":"Roi","userProfilePicture":"https://graph.facebook.com/1016591372/picture?type\u003dlarge","userPassword":"1016591372","userLastName":"Cohen","userJoinDate":1379961412,"userBirthday":571104000,"userId":-1,"userSexCode":1},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":"dadsa","userId":23123}}';
//$jsonData = '{"data":{"userSex":"Man","userEmail":"fffsfgf@dghd.com","userPassword":"Aa12345678","userSexCode":0,"userBirthday":0,"userJoinDate":0},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a964","userId":13810055104469089}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$userActionsObj = new UserActions();
$userActionsObj->user->actionType = "login";
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode($jsonData));

if($userActionsObj->user->convert($mobileActionsObj->mobile->data))
{		
	$couchbaseHandler = new couchbaseMethods();
	$query = $couchbaseHandler->cbh->view("dev_User", "Get_UserOBJ_By_Credentials",array("keys"=>array($userActionsObj->user->userEmail . "_" . $userActionsObj->user->userPassword)));
	
	if($query["rows"] == null) // No user was found
	{
		$wrapper->data = $userActionsObj->preReturn($userActionsObj->user);
		echo $wrapper->returnDataNotExist();
	}
	else
	{
		$couchbaseHandler = new couchbaseMethods();
		$userActionsObj->user->convert($query["rows"][0]["value"]);
		$query2 = $couchbaseHandler->cbh->view("dev_Token", "Get_Token_By_UserID",array("key"=>$userActionsObj->user->userId, "stale"=>false));
		
		// Check if logged from different device
		$device = getDevice($_SERVER['HTTP_USER_AGENT']);
		if($userActionsObj->user->userDevice != $device && $userActionsObj->user->userDevice != "3")
		{
			$userActionsObj->user->userDevice += $device;
			$couchbaseHandler->saveDocument($userActionsObj->user->userId, $userActionsObj->preInsert($userActionsObj->user));// Save the user object
		}
		
		$tokenActionsObj = new TokenActions();
		$tokenActionsObj->token->userId = $userActionsObj->user->userId;
		$tokenActionsObj->token->tokenValue = $query2["rows"][0]["value"];
		
		$query3 = $couchbaseHandler->cbh->view("dev_Item", "Get_Item_By_ConnectedID",array("key"=>$userActionsObj->user->userId));
		
		$userActionsObj->user->userProfilePicture = null;
		if(isset($query3["rows"][0]["value"]))
		{
			$userActionsObj->user->userProfilePicture = $query3["rows"][0]["value"];
		}
		
		$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
		echo $wrapper->returnOK($userActionsObj->preReturn($userActionsObj->user));
	}
}
else // Wrong input provided
{
	echo $wrapper->returnInvalidInput();
}
?>