<?php
include_once 'model/MobileData.php';

class MobileActions
{
	public $mobile;
	
	public function __construct()
	{
		$this->mobile = new MobileData();	
	}
}
?>