<?php
require("includes/uaparser.php");

class couchbaseMethods
{	
	public $cbh;

	public function __construct()
	{
		$this->cbh = new Couchbase("192.168.0.160:8091", "mazeltov", "1q2w3e4r", "mazeltov", TRUE);
	}

	public function saveDocument($docId, $docData)
	{
		try
		{
			$this->cbh->set($docId, json_encode($docData)); // Save the user object
		}
		catch (Exception $e)
		{
			Throw $e;
		}
	}

	public function getDocument($docId)
	{
		return $this->cbh->get($docId);
	}
	
	public function deleteDocument($docId)
	{
		$this->cbh->delete($docId);
	}

	public function validateToken($token, $userId)
	{
		$query = $this->cbh->view("dev_User", "Get_UserID_By_Token",array("keys"=>array($userId."_".$token), "stale"=>false));
		if($query["rows"] == null)
		{
			return false;
		}
		return $query["rows"];
	}

	public function validateGuestInEvent($userId, $eventId)
	{
		$query = $this->cbh->view("dev_Guest", "Get_GuestID_By_UserID_EventID",array("keys"=>array($userId."_".$eventId)));
		if(isset($query["rows"][0]["id"]))
		{
			return true;
		}
		return false;
	}

	public function getUser($userId)
	{
		$user = json_decode($this->cbh->get($userId));
		if($user)
		{
			$user->user->userProfilePicture = null;
			$item = $this->getLastItem($userId, false);
			if($item)
			{
				$user->user->userProfilePicture = $item;
			}
			unset($user->user->userPassword);
			return $user->user;
		}
		return false;
	}
	
	public function getUserByFollow($followId)
	{
		$follow = json_decode($this->cbh->get($followId));
		if($follow)
		{
			$userId = $follow->follow->followUserId;
			$user = json_decode($this->cbh->get($userId));
			if($user)
			{
				$item = $this->getLastItem($userId, false);
				if($item)
				{
					$user->user->userProfilePicture = $item;
				}
				unset($user->user->userPassword);
				
				return $user->user;
			}
		}
		return false;
	}
	
	public function getPost($postId)
	{
		$post = json_decode($this->cbh->get($postId));
		if($post)
		{
			$post->post->postContent = null;
			$item = $this->getLastItem($postId, false);
			if($item)
			{
				$post->post->postContent = $item;
				return $post->post;
			}
		}
		return false;
	}
	
	public function getPostsByDate($eventId, $since, &$array, &$usersArr)
	{
		$from = intval($since);
		$query = $this->cbh->view("dev_Posts", "Get_Posts_By_EventID_PostDate",array("startkey"=>array($eventId,$from),"endkey"=>array($eventId,"\uefff"), "descending"=>false, "limit"=>50));
		if (isset($query["rows"][0]["value"]))
		{
			for($i=0; $i<count($query["rows"]); $i++)
			{
				$object = new stdClass();
				foreach ($query["rows"][$i]["value"] as $key => $value)
				{
	    			$object->$key = $value;
				}
				$object->postContent = null;
				$item = $this->getLastItem($object->postId, false);
				if ($item)
				{
					$object->postContent = $item;
					array_push($array, $object);
				}
				// Check if the user object exist
				if(array_key_exists($object->postPosterId, $usersArr))
				{
					$object->postPoster = $usersArr[$object->postPosterId];
				}
				else 
				{
					$userObj = $this->getUser($object->postPosterId);
					$usersArr[$object->postPosterId] = $userObj;
					$object->postPoster = $userObj;
				}
				
				if(!($object->postPoster))
				{
					$object->postPoster = null;
				}
			}
			if(count($array) > 0)
			{
				return $array;
			}
			return false;
		}
		return false;
	}
	
	public function getPostsByUser($userId, $since, &$array, &$usersArr)
	{
		$from = intval($since);
		$query = $this->cbh->view("dev_Posts", "Get_Posts_By_EventID_PostDate",array("startkey"=>array($userId,$from),"endkey"=>array($userId,"\uefff"), "descending"=>false, "limit"=>50));
		if (isset($query["rows"][0]["value"]))
		{
			for($i=0; $i<count($query["rows"]); $i++)
			{
				$object = new stdClass();
				foreach ($query["rows"][$i]["value"] as $key => $value)
				{
	    			$object->$key = $value;
				}
				$object->postContent = null;
				$item = $this->getLastItem($object->postId, false);
				if ($item)
				{
					$object->postContent = $item;
					array_push($array, $object);
				}
				
				// Check if the user object exist
				if(array_key_exists($object->postPosterId, $usersArr))
				{
					$object->postPoster = $usersArr[$object->postPosterId];
				}
				else 
				{
					$userObj = $this->getUser($object->postPosterId);
					$usersArr[$object->postPosterId] = $userObj;
					$object->postPoster = $userObj;
				}
				
				if(!($object->postPoster))
				{
					$object->postPoster = null;
				}
			}
			if(count($array) > 0)
			{
				return $array;
			}
			return false;
		}
		return false;
	}
	
	public function getEvent($eventId)
	{
		$query = json_decode($this->cbh->get($eventId));
		if($query)
		{
			$eventPicture = $this->getLastItem($query->event->eventId, false);
			if($eventPicture)
			{
				$query->event->eventPicture = $eventPicture;
			}
			
			$query->event->eventHoster = $this->getUser($query->event->eventOrganizer);
			
			$usersArray = array();
			$postsArray = array();
			
			$query->event->eventUsers = null;
			$guests = $this->getGuests($eventId, false);
			if($guests)
			{
				foreach ($guests as $guest)
				{
					$tmpUser = $this->getUser($guest);
					if($tmpUser)
					{
						array_push($usersArray, $tmpUser);
					}
				}
				$query->event->eventUsers = $usersArray;
			}
			
			$query->event->eventPosts = null;
			$posts = $this->getPosts($eventId);
			if($posts)
			{
				foreach ($posts as $post)
				{
					$tmpPost = $this->getPost($post);
					if($tmpPost)
					{
						$tmpPost->postPoster = $this->getUser($tmpPost->postPosterId);
						array_push($postsArray, $tmpPost);
					}
				}
				if(count($postsArray))
				{
					$query->event->eventPosts = $postsArray;
				}
			}
			
			return $query->event;
		}
		return false;
	}
	
	public function getGuests($eventId, $stale)
	{
		if($stale)
		{
			$query = $this->cbh->view("dev_Guest", "Get_Guests_By_EventID",array("key"=>$eventId, "stale"=>false));	
		}
		else
		{
			$query = $this->cbh->view("dev_Guest", "Get_Guests_By_EventID",array("key"=>$eventId));
		}
		if (isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}
	
	public function getPosts($eventId)
	{
		$query = $this->cbh->view("dev_Posts", "Get_Posts_By_EventID",array("key"=>$eventId, "descending"=>true, "limit"=>50));
		if (isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}

	public function getLastItem($connectedId, $stale)
	{
		if($stale)
		{
			$query = $this->cbh->view("dev_Item", "Get_Item_By_ConnectedID",array("key"=>$connectedId, "stale"=>false));			
		}
		else
		{
			$query = $this->cbh->view("dev_Item", "Get_Item_By_ConnectedID",array("key"=>$connectedId));
		}
		
		if (isset($query["rows"][0]["value"]))
		{
			$item = $query["rows"][0]["value"];
			foreach ($query["rows"] as $row)
			{
				if(isset($row["value"]["itemCreateDate"]))
				{
					if ($item["itemCreateDate"] < $row["value"]["itemCreateDate"])
					{
						$item = $row["value"];
					}
				}
			}
			$object = new stdClass();
			foreach ($item as $key => $value)
			{
				$object->$key = $value;
			}
			return $object;
		}
		return false;
	}
	
	public function getEventName($eventId)
	{
		$query = $this->cbh->view("dev_Event", "Get_EventName_By_EventID",array("key"=>$eventId));
		if (isset($query["rows"][0]["value"]))
		{
			return $query["rows"][0]["value"];
		}
		return false;
	}
	
	public function checkifUserExistById($userId)
	{
		$query = json_decode($this->cbh->get($userId));
		if($query)
		{
			return true;
		}
		return false;	
	}
	
	public function getEventLite($eventId)
	{
		$query = json_decode($this->cbh->get($eventId));
		if($query)
		{
			$eventPicture = $this->getLastItem($query->event->eventId, false);
			if($eventPicture)
			{
				$query->event->eventPicture = $eventPicture;
			}
			
			$query->event->eventHoster = $this->getUser($query->event->eventOrganizer);
			if(!($query->event->eventHoster))
			{
				$query->event->eventHoster = null;
			}
			return $query->event;
		}
		return false;
	}
	
	public function getUserNotifications($userId, $from)
	{
		if($from == "0")
		{
			$from = 1;
		}
		$query = $this->cbh->view("dev_Notifications", "Get_Notification_By_UserID",array("startkey"=>array($userId,"\uefff"),"endkey"=>array($userId,$from), "descending"=>true, "limit"=>100));
		
		if (isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			$tempUserArr = array();
			$tempEventArr = array();
			foreach ($query["rows"] as $row)
			{
				//array_push($tempArr, $row["value"]);
				if ($row["value"]["notificationType"] == 1) // Post
				{
					$post = $this->getPost($row["value"]["notificationConnectedId"]);
					if(isset($post->postPosterId))
					{
						$user = $this->getUser($post->postPosterId);
						if(isset($tempUserArr[$post->postPosterId]))
						{
							$post->postPoster = $tempUserArr[$post->postPosterId];
						}
						else
						{
							$tempPostPoster = $this->getUser($post->postPosterId);
							$post->postPoster = null;
							if($tempPostPoster)
							{
								$tempUserArr[$post->postPosterId] = $tempPostPoster;
								$post->postPoster = $tempUserArr[$post->postPosterId];
							}
						}
						$row["value"]["notificationPost"] = $post;
						if(!($row["value"]["notificationPost"]))
						{
							$row["value"]["notificationPost"] = null;
						}
					}
				}
				elseif($row["value"]["notificationType"] == 2) // User
				{
					if(isset($tempUserArr[$row["value"]["notificationConnectedId"]]))
					{
						$row["value"]["notificationFollow"] = $tempUserArr[$row["value"]["notificationConnectedId"]];
					}
					else
					{
						$tempUser = $this->getUserByFollow($row["value"]["notificationConnectedId"]);
						$row["value"]["notificationFollow"] = null;
						if($tempUser)
						{
							$tempUserArr[$row["value"]["notificationConnectedId"]] = $tempUser;  
							$row["value"]["notificationFollow"] = $tempUserArr[$row["value"]["notificationConnectedId"]];
						} 
					}
				}
				elseif($row["value"]["notificationType"] == 3) // Event
				{
					$row["value"]["notificationInvitation"] = $this->getEventLite($row["value"]["notificationConnectedId"]);
					if(!($row["value"]["notificationInvitation"]))
					{
						$row["value"]["notificationInvitation"] = null;
					}
				}
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}
	
	public function getPush($userId)
	{
		$query = $this->cbh->view("dev_Push", "Get_Push_By_UserId",array("key"=>$userId, "stale"=>false));
		if(isset($query["rows"][0]["value"]))
		{
			return $query["rows"][0]["value"];
		}
		return false;
	}
	
	public function getPushToken($push, $ua)
	{
		$parser = new UAParser();
		$result = $parser->parse($ua);
		
		$push->pushOS = $result->os->toString;
		$push->pushDevice = $result->device->family;
		$push->status = "new";
		
		if(strpos($ua,"Android"))
		{
			$push->pushType = 1;
		}
		else // iOS
		{
			$push->pushType = 2;
		}
		
		$query = $this->cbh->view("dev_Push", "Get_Push_By_UserId",array("key"=>$push->pushUserId, "stale"=>false));
		if(isset($query["rows"][0]["value"]))
		{
			foreach ($query["rows"] as $row)
			{
				if($row["value"]["pushToken"] == $push->pushToken) // Check if token already exist
				{
					$push->pushId = $row["value"]["pushId"];
					$push->pushCreateDate = $row["value"]["pushCreateDate"];
					$push->status = "exist";
					return $push;
				}
				else // Check if the token has changed
				{
					if($row["value"]["pushType"] == $push->pushType &&
						$row["value"]["pushOS"] == $push->pushOS &&
						$row["value"]["pushDevice"]== $push->pushDevice)
					{
						$push->status = "update";
						$push->pushId = $row["value"]["pushId"];
						return $push;
					}
				}
			}
		}
		return $push; // User has no push object
	}
	
	public function getPushObjects($userId)
	{
		$query = $this->cbh->view("dev_Push", "Get_Push_By_UserId",array("key"=>$userId, "stale"=>false));
		if(isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}
	
	public function getFollowObjects($userId)
	{
		$query = $this->cbh->view("dev_Follow", "Get_Follow_By_UserID",array("key"=>$userId));
		if(isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}
	
	public function getFollowersObjects($userId)
	{
		$query = $this->cbh->view("dev_Follow", "Get_Followers_By_UserID",array("key"=>$userId));
		if(isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}
	
	public function getRandomUsers() // Not working yet
	{
		$query = $this->cbh->view("dev_User", "Get_User_By_Range",array("startkey_docid"=>"0", "endkey_docid"=>"\uefff"));
		if(isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["value"]);
			}
			return $tempArr;
		}
		return false;
	}
	
	public function getFollowByConnectedId($userId, $connectedId)
	{
		$query = $this->cbh->view("dev_Follow", "Get_Follow_By_UserID_ConnecteId",array("key"=>$userId . "_" . $connectedId, "stale"=>false));
		if(isset($query["rows"][0]["value"]))
		{
			return $query["rows"][0]["value"];
		}
		return false;
	}
	
	public function deployNotifications($connectedId)
	{
		$payload = json_encode($connectedId);
		$cmd = "curl -X POST -H 'Content-Type: application/json'";
		$cmd.= " -d '" . $payload . "' " . "'http://127.0.0.1/MazelTov/WebDev/mtpushnotifications.php'";
		  
		$cmd .= " > /dev/null 2>&1 &";
		
		exec($cmd, $output, $exit);
		return $exit == 0;
	}
	
	public function getItems($connectedId)
	{
		$query = $this->cbh->view("dev_Item", "Get_Item_By_ConnectedID",array("key"=>$connectedId));
		if (isset($query["rows"][0]["value"]))
		{
			$tempArr = array();
			foreach ($query["rows"] as $row)
			{
				array_push($tempArr, $row["itemId"]);
			}
			return $tempArr;
		}
		return false;		
	}
	
	public function deleteOldItems($connectedId)
	{
		$delArr = $this->getItems($connectedId);
		foreach ($delArr as $del)
		{
			$this->deleteDocument($del);
		}
	}
}
?>