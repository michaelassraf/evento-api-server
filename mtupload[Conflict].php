<?php
define('!@@!CH_Includer@!!@',true);
include_once 'includes/utils.php';

include_once 'actions/ItemActions.php';
include_once 'actions/TokenActions.php';
include_once 'actions/MobileActions.php';
include_once 'actions/Wrapper.php';
include_once 'controllers/couchbaseMethods.php';

//$jsonData = '{"data":{"userSex":"Man","userEmail":"rcohenk@gmail.com","userFirstName":"Roi","userProfilePicture":"https://graph.facebook.com/1016591372/picture?type\u003dlarge","userPassword":"1016591372","userLastName":"Cohen","userJoinDate":1379961412,"userBirthday":571104000,"userId":-1,"userSexCode":1},"meta":{"statusCode":"SUCCESS","statusMessage":"","token":"dadsa","userId":23123}}';
//$jsonData = '{"data":{"itemType":"AVI","itemId":0,"connectedId":1122334455},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a962","userId":13810055104469089}}}';
//$jsonData = '{"data":{"connectedId":13844606947449172,"itemType":"AVI"},"meta":{"token":{"tokenValue":"6597f3779d1594c14cdd1f56e84c6a19","userId":13844606947449172}}}';
//$jsonData = '{"data":{"connectedId":13850527829337963,"itemType":"PNG"},"meta":{"token":{"tokenValue":"2c834af7fce6623dddec016fbd90a964","userId":13810055104469089}}}';
//$jsonData = '{"data":{"connectedId":13854152557279821,"itemType":"JPG"},"meta":{"token":{"tokenValue":"4a68e5061399c094ea6c0828d0428830","userId":13852058266871149},"statusCode":0}}';
$jsonData = $_POST["jsonData"];

$itemActionsObj = new ItemActions();
$wrapper = new Wrapper();

$mobileActionsObj = new MobileActions();
$mobileActionsObj->mobile->convert(json_decode(preg_replace('/("\w+"):(\d+)/', '\\1:"\\2"', $jsonData)));

if($itemActionsObj->item->convert($mobileActionsObj->mobile->data))
{
	$tokenActionsObj = new TokenActions();
	
	if($tokenActionsObj->token->convert($mobileActionsObj->mobile->meta->token)) // Check given token validity
	{
		
		$couchbaseHandler = new couchbaseMethods();
		$data = $couchbaseHandler->validateToken($tokenActionsObj->token->tokenValue, $tokenActionsObj->token->userId);
		
		if($data) // Check token in the DB
		{
			$document = $couchbaseHandler->getDocument($itemActionsObj->item->connectedId);
			
			if($document) // Check if there is a document
			{
				$object = json_decode($document);
				$check = check_object($object, $tokenActionsObj->token->userId);
				if ($check)
				{
					$objFile = & $_FILES;
					
					$itemActionsObj->item->itemId = $itemActionsObj->generate();
					$extension = get_file_extension(basename($objFile["file"]["name"]));
				
					if($extension) // Not null
					{	
						$filename = $itemActionsObj->item->connectedId."_".$itemActionsObj->item->itemId.".".$extension;
    					$strPath = "/var/www/MazelTov/storage/".$filename;
    			
						$file_info = new finfo(FILEINFO_MIME);  // object oriented approach!
 						$mime_type = $file_info->buffer(file_get_contents($objFile["file"]["tmp_name"]));  // e.g. gives "image/jpeg"
 						$upload_type = typeValidator($mime_type);
 						
 						//$itemActionsObj->item->itemType = $mime_type;
 						$itemActionsObj->item->itemType = "image/jpeg; charset=binary";
 						
						if($upload_type == 1) // Image
						{
							if(move_uploaded_file( $objFile["file"]["tmp_name"], $strPath ))
							{
								$itemActionsObj->item->itemUrl = $itemActionsObj->item->connectedId."_".$itemActionsObj->item->itemId.".jpg";
								if($check == 1 || $check == 2) // Delete old item
								{
									$couchbaseHandler->deleteOldItems($itemActionsObj->item->connectedId);
								}
								// Reduce image size
								shell_exec("convert ".$strPath." -format jpg -quality 50 /var/www/MazelTov/storage/".$itemActionsObj->item->connectedId."_".$itemActionsObj->item->itemId.".jpg");
								if($extension != 'jpg')
								{
									shell_exec("rm ".$strPath." 2>&1");
								}
								
								$couchbaseHandler->saveDocument($itemActionsObj->item->itemId, $itemActionsObj->preInsert($itemActionsObj->item));
								$tokenActionsObj->token->tokenValue = $data[0]["value"];
								$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
								echo $wrapper->returnOK(null);
								if($check == 3)
								{
									$couchbaseHandler->deployNotifications($itemActionsObj->item->connectedId);
								}
							}
							else
							{
								echo $wrapper->returnError("Upload failed");
							}
						}
						elseif($upload_type == 2 && $check == 3) // Video + Upload to post
						{
							$playBtnPath = "/var/www/MazelTov/play.png";
							$tmpPath = "/var/tmp/".$filename;
							$finalPath = "/var/www/MazelTov/storage/".$itemActionsObj->item->connectedId."_".$itemActionsObj->item->itemId.".mp4";
							if(move_uploaded_file( $objFile["file"]["tmp_name"], $tmpPath))
							{
								$thumbnailPath = "/var/www/MazelTov/storage/".$itemActionsObj->item->connectedId."_".$itemActionsObj->item->itemId.".jpg";
								$check2 = exec('mediainfo '.$tmpPath.' | grep "Rotation"');
								if($check2)
								{
									shell_exec("ffmpeg -y -i ".$tmpPath." -s 432x320 -b 384k -vcodec libx264 -flags +loop+mv4 -cmp 256 -partitions +parti4x4+parti8x8+partp4x4+partp8x8 -subq 6 -trellis 0 -refs 5 -bf 0 -flags2 +mixed_refs -coder 0 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -qmin 10 -qmax 51 -qdiff 4 -acodec libvo_aacenc -ac 1 -ar 16000 -r 13 -ab 32000 -vf 'transpose=1' ".$finalPath);
								}
								else
								{
									shell_exec("ffmpeg -y -i ".$tmpPath." -s 432x320 -b 384k -vcodec libx264 -flags +loop+mv4 -cmp 256 -partitions +parti4x4+parti8x8+partp4x4+partp8x8 -subq 6 -trellis 0 -refs 5 -bf 0 -flags2 +mixed_refs -coder 0 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -qmin 10 -qmax 51 -qdiff 4 -acodec libvo_aacenc -ac 1 -ar 16000 -r 13 -ab 32000 ".$finalPath);
								}
								// Create thumbnail
								$time = getVideoLength($finalPath);
								shell_exec("ffmpeg -i ".$finalPath." -vframes 1 -an -ss ".$time." ".$thumbnailPath);
								// Add play button
								shell_exec("convert ".$thumbnailPath." ".$playBtnPath." -gravity center -composite -format jpg -quality 90 ".$thumbnailPath);
								// Fast-start
								shell_exec("qt-faststart ".$finalPath." ".$finalPath." 2>&1");
								
								$itemActionsObj->item->itemUrl = $itemActionsObj->item->connectedId."_".$itemActionsObj->item->itemId.".".$extension;
								$couchbaseHandler->saveDocument($itemActionsObj->item->itemId, $itemActionsObj->preInsert($itemActionsObj->item));
								$tokenActionsObj->token->tokenValue = $data[0]["value"];
								$wrapper->setReturnToken($tokenActionsObj->preReturn($tokenActionsObj->token));
								echo $wrapper->returnOK(null);
								if($check == 3)
								{
									$couchbaseHandler->deployNotifications($itemActionsObj->item->connectedId);
								}
							}
							else
							{
								echo $wrapper->returnError("Upload failed");
							}
						}
						else 
						{
							echo $wrapper->returnWrongType();
						}
					}
					else
					{
						echo $wrapper->returnWrongType();	
					}
				}
				else
				{
					echo $wrapper->returnInvalidInput();
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

function typeValidator($mime_type)
{
	/* Validate images */
	$imageTypes = array('png' => 'image/png', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpg', 'gif' => 'image/gif');
	foreach ($imageTypes as $imageType)
	{
		if(strpos($mime_type, $imageType) !== false)
		{
			return 1;
		}
	}
	
	/* Validate videos */
	$videoTypes = array('3gp' => 'video/3gpp', 'mp4' => 'video/mp4', 'mpeg' => 'video/mpeg', 'mov' => 'video/quicktime');
	foreach ($videoTypes as $videoType)
	{
		if(strpos($mime_type, $videoType) !== false)
		{
			return 2;
		}
	}
	return false;
}

function get_file_extension($file_name) 
{
	return substr(strrchr($file_name,'.'),1);
}

function check_object($object, $userId)
{
	if(isset($object->user))
	{
		if ($object->user->userId == $userId)
		{
			return 1;
		}
	}
	elseif(isset($object->event))
	{
		if ($object->event->eventOrganizer == $userId)
		{
			return 2;
		}
	}
	elseif(isset($object->post))
	{
		if ($object->post->postPosterId == $userId)
		{
			return 3;
		}
	}
	else
	{
		return false;
	}
}

function getVideoLength($path)
{
	$xyz = shell_exec("ffmpeg -i ".$path." 2>&1");

	$search='/Duration: (.*?),/';
	preg_match($search, $xyz, $matches);
	$explode = explode(':', $matches[1]);
	$totalSec = $explode[0] * 3600 + $explode[1] * 60 + intval($explode[2]);

	return intval($totalSec/2);
}
?>