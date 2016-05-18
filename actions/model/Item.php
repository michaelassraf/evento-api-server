<?php
include_once 'ModelBase.php';

class Item extends ModelBase
{
	public $itemId;
	public $itemUrl; // Filename
	public $itemType;
	public $connectedId; // Document ID - Event / User / Post
	public $itemCreateDate; // Timestamp
	
	public function checkValidity()
	{
		if($this->connectedId == null && intval($this->connectedId))
		{
			return false;
		}
		return $this;
	}
}
?>