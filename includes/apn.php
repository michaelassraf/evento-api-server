<?php
class apn
{
	public function sendMessage($token, $fullName, $type)
	{
		// Put your device token here (without spaces):
		$deviceToken = $token;
		
		// Put your private key's passphrase here:
		$passphrase = '1q2w3e4r';
		
		$added_message = "";
		// Put your alert message here:
		if($type == 1) // Post
		{
			$added_message = " has uploaded a new post !";
		}
		elseif ($type == 2) // User
		{
			$added_message = " is now following you !";
		}
		else // Event
		{
			$added_message = " invited you to an event !";
		}
		
		$message = $fullName . $added_message;
		
		////////////////////////////////////////////////////////////////////////////////
		
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', '/var/cert/mazeltov/apn/Dev.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		stream_context_set_option($ctx, 'ssl', 'cafile', '/var/cert/mazeltov/apn/entrust_2048_ca.cer');
		
		// Open a connection to the APNS server
		$fp = stream_socket_client(
		'ssl://gateway.sandbox.push.apple.com:2195', $err,
			$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		
		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
		
		echo 'Connected to APNS' . PHP_EOL;
		
		// Create the payload body
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default'
			);
		
		// Encode the payload as JSON
		$payload = json_encode($body);
		
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
		
		if (!$result)
			echo 'Message not delivered' . PHP_EOL;
		else
			echo 'Message successfully delivered' . PHP_EOL;
		
		// Close the connection to the server
		fclose($fp);
	}
}
?>