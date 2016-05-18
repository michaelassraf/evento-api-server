<?php
include_once 'ModelBase.php';

class Search extends ModelBase
{
	public $searchQuery = "";
	public $searchType = "";
	
	public function checkValidity()
	{
		if(strlen($this->searchQuery) == 0 || strlen($this->searchQuery) > 50)
		{
			return false;
		} 
		return $this;
	}
}
?>