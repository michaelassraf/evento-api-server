<?php
include_once 'ModelBase.php';

class MobileData extends ModelBase
{
	public $data;
	public $meta;
	
	public function checkValidity()
	{
		return $this;
	}
}
?>