<?php
include_once 'ModelBase.php';
include_once 'Item.php';

class Post extends ModelBase
{
	public $postId;
	public $postEventId; // Event connected to
	public $postEventName; // Event connected to
	public $postPoster; // User created the post (full name)
	public $postPosterId; // User created the post (userId)
	public $postTitle; // Post title - Received from view
	public $postContent; // Post content (URL or text)
	public $postType; // 1 - Photo; 2 - Video; 3 - Text
	public $postCreateDate; // Timestamp
	public $postLikes; // Number of likes 
	
	public function __construct()
	{
		$this->postContent = new Item();
	}
	
	public function checkValidity()
	{
		if($this->postEventId == '')
		{
			return false;
		}
		return $this;
	}
}
?>