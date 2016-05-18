<?php
include_once 'ModelBase.php';
include_once 'Item.php';

class User extends ModelBase
{
	public $userId;
	public $userFirstName;
	public $userLastName;
	public $userEmail;
	public $userPassword;
	public $userProfilePicture; // Item
	public $userBirthday; // Timestamp
	public $userSex; // 1 - male; 2 - female
	public $userSexCode; // 1 - male; 2 - female
	public $userDevice; // 1 - iOS, 2 - Android; 3 - iOS + Android; 0 - None 
	public $actionType; // Used for determine which action required - register or login
	public $userJoinDate; // Timestamp
	public $userMazelPoints; // Future points
	
	public function __construct()
	{
		$this->userProfilePicture = new Item();
	}
	
	public function checkValidity()
	{
		if($this->actionType == "register")
		{
			if($this->userFirstName == '' || $this->userLastName == '' || $this->userEmail == '' || $this->userPassword == '' || $this->userBirthday == '' || $this->userSexCode == '')
			{
				return false;
			}
			else if(!(filter_var($this->userEmail, FILTER_VALIDATE_EMAIL))) 
			{
				return false;
			}
			else if(!(intval($this->userBirthday) && intval($this->userSexCode)))
			{
				return false;
			}
		}
		else if($this->actionType == "login")
		{
			if($this->userEmail == '' || $this->userPassword == '')
			{
				return false;
			}
			else if(!(filter_var($this->userEmail, FILTER_VALIDATE_EMAIL))) 
			{
				return false;
			}
		}
		else if($this->actionType == "search")
		{
			if($this->userFirstName == '' || $this->userLastName == '')
			{
				return false;
			}
		}
		else if($this->actionType == "query")
		{
			if($this->userId == '')
			{
				return false;
			}
		}
		$this->encrypter();
		return $this;
	}
	
	public function encrypter() // Encrypt the password
	{
		$this->userPassword = md5($this->userPassword . "ch1b1@C3iBi" . $this->userPassword);
	}
}
?>