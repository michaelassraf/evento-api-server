<?php
class gcm
{
	public function sendMessage($token, $notification)
	{
		$url = 'https://android.googleapis.com/gcm/send';
		$fields = array(
						'registration_ids' => array($token),
						'data'             => json_decode($notification),
						'time_to_live'     => 2,
						'delay_while_idle' => true,
						);
		
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
	
		return $result;
	}
}
?>