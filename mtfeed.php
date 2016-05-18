<?php
include_once 'actions/TokenActions.php';
include_once 'actions/EventActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/RequestActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"since":12345678,"calltype":"launch"},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
//$jsonData = '{"data":{"callType":"PostInit","since":0},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a964","userId":13810055104469089}}}';
//$jsonData = '{"data":{"callType":"PostInit","since":0},"meta":{"token":{"tokenValue":"47d296216fd5c12932d5742ae903d3a2","userId":13833960874835817}}}';
//$jsonData = '{"data":{"callType":"PostInit","since":0},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a964","userId":13810055104469089},"statusCode":0}}';
$jsonData = $HTTP_RAW_POST_DATA;

$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

$requestActionsObj = new RequestActions();

if($requestActionsObj->request->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();

	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token)) // Check given token validity
	{
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);

		if($data) // Check token in the DB
		{
			if($requestActionsObj->request->callType == "PostInit" || $requestActionsObj->request->callType == "PostPull")
			{
				$query = $couchbaseHandler->cbh->view("dev_Guest", "Get_Events_By_UserID",array("key"=>$tokenActionsObj->token->userId));
				$events_ids = get_events($query["rows"]);
				if($events_ids)
				{
					$events_array = array();
					$users_array = array();
					foreach ($events_ids as $event_item) // Get posts from invited events
					{
						$couchbaseHandler->getPostsByDate($event_item, $requestActionsObj->request->since, $events_array, $users_array);
					}
					if (count($events_array) < 50) // Get posts from users this user is following
					{
						$following = $couchbaseHandler->getFollowObjects($tokenActionsObj->token->userId);
						if(count($following))
						{
							foreach ($following as $follow)
							{
								$couchbaseHandler->getPostsByUser($follow["followUserId"], $requestActionsObj->request->since, $events_array, $users_array);
							}
						}
						//$events_array = distinct_posts($events_array);
					}
					if (count($events_array) < 50) // Get posts from followers
					{
						$following = $couchbaseHandler->getFollowersObjects($tokenActionsObj->token->userId);
						if(count($following))
						{
							foreach ($following as $follow)
							{
								$couchbaseHandler->getPostsByUser($follow["followUserId"], $requestActionsObj->request->since, $events_array, $users_array);
							}
						}
						//$events_array = distinct_posts($events_array);
					}
					if(count($events_array))
					{
						$tokenActionsObj->token->tokenValue = $data[0]["value"];
						$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
						echo $wrapper->returnOK($events_array);
					}
					else
					{
						echo $wrapper->returnDataNotExist();
					}
				}
				else
				{
					echo $wrapper->returnDataNotExist();
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

// Get event ID from query
function get_events($array)
{
	$tempArray = array();
	if(isset($array[0]))
	{
		foreach ($array as $row)
		{
			array_push($tempArray, $row["value"]);
		}
		return $tempArray;
	}
	return false;
}

// Check if the date has passed
function check_end_date($row, $since)
{
	$row = json_decode($row);
	if($row->event->postCreateDate > $since)
	{
		return $row;
	}
	return false;
}

function distinct_posts($array)
{
	$postIds = array();
	foreach ($array as $key => $item)
	{
		if (in_array($item->postId, $postIds))
		{
			unset($array[$key]);
		}
		else
		{
			array_push($postIds, $item->postId);
		}
	}
	$temp_arr = array();
	foreach ($array as $item)
	{
		array_push($temp_arr, $item);
	}
	return $temp_arr;
}
?>