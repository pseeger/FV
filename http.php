<?php

if (! @trim(file_get_contents('developer.txt')))
error_reporting(E_ERROR);


if(!isset($_COOKIE['user'])) die('Couldn\'t find any cookie.');
$userId = $_COOKIE['user']; 
if(!isset($_GET['url'])) die('No URL found.');
$file = $_GET['url'];
if(!isset($_GET['plugin'])) die('No plugin specified');
$plugin = $_GET['plugin'];


if(strpos($file,'main.php')===0){
	include('parser.php');
	define ('farmer', GetFarmserver());
	define ('farmer_url', GetFarmUrl());
	error_reporting(E_ALL);
	$this_plugin['folder']='plugins/'.$plugin.'/';
	echo 'Calling function';
	include('plugins/'.$plugin.'/main.php');
	$form_function = $plugin. '_form';
	if (function_exists($form_function)) {
		call_user_func($form_function);
	}
}
else include('plugins/'.$plugin.'/'.$file);
?>
