<?php
include_once 'Response.php';

class ModelBase
{
	public function convert($json) // Convert a JSON to class
	{
		foreach ($json AS $key => $value) 
			$this->{$key} = $value;
		$response = $this->checkValidity();
		return $response;	
	}
	
	public function convertAdvanced($json) // Convert a JSON to class
	{
		foreach ($json AS $item)
			foreach ($item AS $key2 => $value2) 
				$this->{$key2} = $value2;
		$response = $this->checkValidity($this);
		return $response;	
	}
}
?>