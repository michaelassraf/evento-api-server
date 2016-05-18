<?php
include_once 'ModelBase.php';
include_once 'Item.php';

class Event extends ModelBase
{

//need to implement the validate method, no need to create the set method it's in the ModelBase class	

	public $eventId;
	public $eventType; // 1 - Wedding; 2 - Bar Mitzva; 3 - Brithday; 4 - Bris; 5 - Brita; 6 - Party;  
	public $eventOrganizer; // User ID
	public $eventHoster; // User profile
	public $eventName;
	public $eventDescription; // Agenda
	public $eventSummary; // Highlights   
	public $eventLocation; // Place
	public $eventPicture; // Item object
	public $eventStartDate; // Timestamp
	public $eventEndDate; // Timestamp
	public $eventCreateDate; // Timestamp
	public $eventUsers; // Users holder
	public $eventPosts; // Posts holder

	public function __construct()
	{
		$this->eventPicture = new Item();
	}
	
	public function checkValidity()
	{
		return $this;
	}
}
?>