<?php
include_once 'ActionsBase.php';
include_once 'ActionsInterface.php';
include_once 'model/Post.php';

class PostActions extends ActionsBase implements ActionsInterface
{
	public $post;
	
	public function __construct()
	{
		$this->post = new Post();
	}
	
	public function newPost()
	{
		$this->post[0] = new Post();
		$this->post->postContent->itemId = $this->generate();
		$this->post->postId = $this->generate();
	}
	
	public function addPost($postItem)
	{
		array_push($this->post, $postItem);
	}
	
	public function preInsert($data)
	{
		$this->post->postCreateDate = strtotime("now");
		$this->post->postContent = null;
		return $this->DBWrapper($data);
	}
}
?>