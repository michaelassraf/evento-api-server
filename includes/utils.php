<?php

#error_reporting(E_ALL);
#ini_set('display_errors', '1');

if(!defined('!@@!CH_Includer@!!@')) die('You are not allowed to execute this file directly');
{
	//----------General functions----------------//
	
	function getDevice($header) // Get user device OS
	{
		$iPod    = strpos($header,"iPod");
		$iPhone  = strpos($header,"iPhone");
		$iPad    = strpos($header,"iPad");
		$Android = strpos($header,"Android");
		
		if($iPod || $iPhone || $iPad)
		{
			return 1;
		}
		elseif($Android) // Wise choise :-)
		{
			return 2;
		}
		else
		{
			return 0;
		}
	}
	//----------End general functions----------------//
}
?>