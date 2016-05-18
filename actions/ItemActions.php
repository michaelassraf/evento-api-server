<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Item.php';

class ItemActions extends ActionsBase implements ActionsInterface
{
	public $item;
	
	public function __construct()
	{
		$this->item = new Item();
	}
	
	public function preInsert($data)
	{
		$this->item->itemCreateDate = strtotime("now");
		return $this->DBWrapper($data);
	}
}
?>