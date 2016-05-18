<?php
include_once 'actions/TokenActions.php';
include_once 'actions/EventActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/RequestActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"since":0,"calltype":"event"},"meta":{"token":{"tokenValue":"8db412c8f35a530a139a7649634061f3","userId":13858151849825562}}}';
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
			// Get all guest instances
			$query = $couchbaseHandler->cbh->view("dev_Guest", "Get_Events_By_UserID",array("key"=>$tokenActionsObj->token->userId));
			$events_ids = get_events($query["rows"]);
			if($events_ids)
			{
				$events_array = array();
				foreach ($events_ids as $event_item)
				{
					$row = $couchbaseHandler->getDocument($event_item);
					$row = check_end_date($row, $requestActionsObj->request->since);
					if($row)
					{
						$query3 = $couchbaseHandler->cbh->view("dev_Item", "Get_Item_By_ConnectedID",array("key"=>$row->event->eventId));
						$row->eventPicture = null;
						if(isset($query3["rows"][0]["value"]))
						{
							$row->event->eventPicture = $query3["rows"][0]["value"];
						}
						array_push($events_array, $row->event);
					}
				}
				if($events_array)
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
	if(($row->event->eventEndDate > strtotime("now")) || ($since < $row->event->eventCreateDate))
	{
		return $row;
	}
	return false;
}