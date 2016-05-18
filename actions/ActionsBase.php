<?php
class ActionsBase
{
	public function DBWrapper($data)
	{
		$name = strtolower(get_class($data));
		$objectItem = new stdClass();
		$objectItem->$name = $data;
		return $objectItem;
	}
	
	public function generate() // Generate a unique id for object
	{
		$characters = '0123456789';
    	$randomString = '';
    	for ($i = 0; $i < 7; $i++) 
		{
       		$randomString .= $characters[rand(0, strlen($characters) - 1)];
    	}
    	return floor(microtime(true)).$randomString;
	}
	
	public function getMSDate()
	{
		$utimestamp = microtime(true);

		$timestamp = floor($utimestamp);
		$milliseconds = round(($utimestamp - $timestamp) * 1000000);

		return date("Y-m-d").'T'.date("H:i:s").'.'.round($milliseconds/1000).'Z';
	} 
}
?>