<?php
include_once 'actions/MobileActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/Wrapper.php';
include_once 'actions/SearchActions.php';
include_once 'actions/UserActions.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"searchQuery":"roi","searchType":""},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
$jsonData = $HTTP_RAW_POST_DATA;

$searchActionsObj = new SearchActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($searchActionsObj->search->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();
	
	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token)) // Check given token validity
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);
		
		if($data) // Check token in the DB
		{
			$searchActionsObj->search->searchQuery = strtolower($searchActionsObj->search->searchQuery);
			$query = $couchbaseHandler->cbh->view("dev_Search", "Get_Users",array("key"=>$searchActionsObj->search->searchQuery));
			$queryArray = distinct_result($query["rows"]);
			if($queryArray) // Check if there is a search result
			{
				$return_data = array();
				for($i = 0; $i < count($queryArray["value"]); $i++)
				{
					$userActionsObj = new UserActions();
					$userActionsObj->user->userId = $queryArray["value"][$i][0];
					$userActionsObj->user->userFirstName = $queryArray["value"][$i][1];
					$userActionsObj->user->userLastName = $queryArray["value"][$i][2];
					
					$query3 = $couchbaseHandler->cbh->view("dev_Item", "Get_Item_By_ConnectedID",array("key"=>$userActionsObj->user->userId));
					
					$userActionsObj->user->userProfilePicture = null;
					if(isset($query3["rows"][0]["value"]))
					{
						$userActionsObj->user->userProfilePicture = $query3["rows"][0]["value"];
					}
					array_push($return_data, $userActionsObj->preReturn($userActionsObj->user));
				}
				$tokenActionsObj->token->tokenValue = $data[0]["value"];
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
				echo $wrapper->returnOK($return_data);
			}
			else
			{
				$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
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
else // Wrong input provided
{
	echo $wrapper->returnInvalidInput();
}

function distinct_result($array)
{
	if(isset($array[0]))
	{
		$counter = 0;
		$tempArr = array();	
		$tempIDs = array();
		
		foreach ($array as $row)
		{
			if(!(in_array($row["value"][0], $tempIDs)))
			{
				array_push($tempIDs, $row["value"][0]);
				$tempArr["value"][$counter] = $row["value"];
				$counter++;
			}
		}
		return $tempArr;
	}
	return false;
}
?>
