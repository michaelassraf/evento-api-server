<?php
include_once 'model/Search.php';

class SearchActions
{
	public $search;
	
	public function __construct()
	{
		$this->search = new Search();
	}
}
?>