<?php
define('fvNeighbors_version', '2.14');
define('fvNeighbors_date', 'March 2011');
define('fvNeighbors_URL', '/plugins/fvNeighbors/main.php');
define('fvNeighbors_Path', 'plugins/fvNeighbors/');
// file definitions
define('fvNeighbors_Main', 'fvNeighbors_main.sqlite');
////
define('fvNeighbors_name', 'fvNeighbors_name.sqlite');
////

require_once('includes/GridServerHandler.php');
require_once('includes/JSON.php');
require_once('includes/ExcelExport.php');
include 'includes/fvNeighbors.class.php';
include 'includes/fvNeighbors_form.php';

if (!function_exists('AddRewardLog')) {
	function AddRewardLog($rewardName, $url) {
		$f = fopen(F('Rewards-Day' . @date('z') . '.txt'), "a");
		if ($f) {
			fputs($f, @date("H:i:s") . " \t$rewardName \thttp://apps.facebook.com/onthefarm/$url \r\n\r\n");
			fclose($f);
		}
	}
}

function fvNeighbors_init(){
	global $hooks;
	global $this_plugin;
	global $is_debug;

	$hooks['after_planting'] = 'fvNeighbors_doWork';
}

function fvNeighbors_doWork(){
	global $this_plugin;
	error_reporting(E_ALL | E_PARSE | E_NOTICE | E_WARNING);
	ini_set('display_errors', true);
	
  AddLog2('fvNeighbors initializing');
  
	$fvM = new fvNeighbors();
	error_reporting(0);
	ini_set('display_errors', false);
  
	if($fvM->error != ''){
		AddLog2($fvM->error);
		unset($fvM);
		return;
	}

	AddLog2('fvNeighbors finished');
	unset($fvM);
}

function fvNeighbors_Refresh(){
	$fvM = new fvNeighbors();
	unset($fvM);
}

?>