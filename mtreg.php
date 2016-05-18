<?php
define('!@@!CH_Includer@!!@',true);
include_once 'includes/utils.php';

include_once 'actions/UserActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/ItemActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"userSex":"Man","userEmail":"rcohenk@gmail.com","userFirstName":"Roi","userProfilePicture":"https://graph.facebook.com/1016591372/picture?type\u003dlarge","userPassword":"1016591372","userLastName":"Cohen","userJoinDate":1379961412,"userBirthday":571104000,"userId":-1,"userSexCode":1},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":"dadsa","userId":23123}}';
//$jsonData = '{"data":{"userSex":"Man","userEmail":"rcohenk@gmail2.com","userFirstName":"Roi","userProfilePicture":{"itemId": "","itemUrl": "https://graph.facebook.com/712801137/picture?type=large","itemType": "","connectedId": ""},"userPassword":"1016591372","userLastName":"Cohen","userJoinDate":1379961412,"userBirthday":571104000,"userId":-1,"userSexCode":1},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":"dadsa","userId":23123}}';
$jsonData = $HTTP_RAW_POST_DATA;

$userActionsObj = new UserActions();
$userActionsObj->user->actionType = "register";
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode($jsonData));

if($userActionsObj->user->convert($mobileActionsObj->mobile->data))
{	
	$userActionsObj->user->userDevice = getDevice($_SERVER['HTTP_USER_AGENT']);
	$userActionsObj->user->userId = $userActionsObj->generate();
	
	$tokenActionsObj = new TokenActions();
	$couchbaseHandler = new couchbaseMethods();
	//var_dump($userActionsObj->user);
	try 
	{
		$query = $couchbaseHandler->cbh->view("dev_User", "Get_UserID_By_Email",array("key"=>$userActionsObj->user->userEmail, "stale"=>false));
		
		if($query["rows"] == null)
		{
			if(!(strpos($userActionsObj->user->userProfilePicture->itemUrl, "facebook") !== false))
			{
				$userActionsObj->user->userProfilePicture = null;
			}
			else
			{
				$itemActionsObj = new ItemActions();
				$itemActionsObj->item->itemUrl = $userActionsObj->user->userProfilePicture->itemUrl;
				$itemActionsObj->item->itemId = $itemActionsObj->generate();
				$itemActionsObj->item->connectedId = $userActionsObj->user->userId;
				$itemActionsObj->item->itemType = 'image/jpeg';
				
				$couchbaseHandler->saveDocument($itemActionsObj->item->itemId, $itemActionsObj->preInsert($itemActionsObj->item));// Save the user object
				
				$userActionsObj->user->userProfilePicture = null;
			}
			$couchbaseHandler->saveDocument($userActionsObj->user->userId, $userActionsObj->preInsert($userActionsObj->user));// Save the user object
			$tokenActionsObj->token->userId = $userActionsObj->user->userId;
			$couchbaseHandler->saveDocument($tokenActionsObj->token->tokenId, $tokenActionsObj->preInsert($tokenActionsObj->token)); // Save the token object
			$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
			echo $wrapper->returnOK($userActionsObj->preReturn($userActionsObj->user));			
		}
		else // Dupliacte record
		{
			$wrapper->data = $userActionsObj->preReturn($userActionsObj->user);
			echo $wrapper->returnDataExist();	
		}
	}
	catch (Exception $e) // Error
	{
		echo $wrapper->returnError($e);
	}
}
else // Wrong input provided
{
	echo $wrapper->returnInvalidInput();
}
?>