<?php
$notification = '{"data":{"notificationId":"13863247273620426","notificationUserId":"13858151849825562","notificationType":1,"notificationDate":1386324727,"notificationConnectedId":"13863247190146179","notificationFullName":"Roi Cohen","notificationPost":{"post":{"postId":"13863247190146179","postEventId":"13861495287001484","postEventName":"A Day At Office","postPoster":null,"postPosterId":"13858155597220121","postTitle":"","postContent":{"itemId":null,"itemUrl":null,"itemType":null,"connectedId":null,"itemCreateDate":null},"postType":"Image","postCreateDate":1386324719,"postLikes":"0"},"postPoster":{"userId":"13858155597220121","userFirstName":"Roi","userLastName":"Cohen","userEmail":"rcohenk@gmail.com","userProfilePicture":{"itemId":"13858155591011254","itemUrl":"https:\/\/graph.facebook.com\/1046993046\/picture?type=large","itemType":"image\/jpeg","connectedId":"13858155597220121","itemCreateDate":1385815559},"userBirthday":539827207200,"userSex":"Man","userSexCode":1,"userDevice":2,"userJoinDate":1385815644,"userMazelPoints":null}}},"meta":{"statusCode":"10","statusMessage":"OK","token":null}}';
		$url = 'https://android.googleapis.com/gcm/send';
		$fields = array(
						'registration_ids' => array("APA91bEulc3D860usjCs3nJCppiyYQP1Dwjm_u1jn2Qzn7MQk-6YteeFP3ptqSQRGeXwt6r7yOmVcWZc22NM-ayg9zaukRqZdQuzODR8ZjMTy67wX7p26RmQgIww-Y6AafrKETLRDBDS2QH6OuVoeEHNiX9wQaloFw"),
						'data'             => json_decode($notification),
						'time_to_live'     => 2,
						'delay_while_idle' => true,
						);
		
						var_dump(json_encode($fields));
		
		$headers = array( 
							'Authorization: key=AIzaSyCfK7Z41RvpKsETugteBfDK27zJ42qeEZ4',
							'Content-Type: application/json'
						);
		
		// Open connection
		$ch = curl_init();
		
		// Set the url, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $url );
		
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
		
		// Execute post
		$result = curl_exec($ch);
		
		// Close connection
		curl_close($ch);
		
		echo $result;
?>