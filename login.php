<?php
include('config/accounts.php');

$cmd=array();
foreach($accounts as $login_email=>$login_pass) {
	$ch = curl_init();
	curl_setopt_array($ch,array(CURLOPT_URL=>'http://apps.facebook.com/onthefarm/index.php',
			CURLOPT_USERAGENT=> 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
			CURLOPT_FOLLOWLOCATION=>true,
			CURLOPT_SSL_VERIFYPEER=>false,
			CURLOPT_COOKIEJAR=>'cookies.txt',
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_HEADER=>false));
	curl_exec($ch);
	curl_setopt($ch, CURLOPT_URL, 'https://login.facebook.com/login.php?login_attempt=1&next=http://apps.facebook.com/onthefarm/index.php?ref=bookmarks');
	curl_setopt($ch, CURLOPT_POSTFIELDS,'email='.urlencode($login_email).'&pass='.urlencode($login_pass).'&login=Login');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res=curl_exec($ch);

	$c = curl_init();
	preg_match('/iframe.+?src="(.+?flash.+?)"/',$res,$url);
	curl_setopt_array($c, array(CURLOPT_USERAGENT=> 'Mozilla/5.0 (Windows, U, Windows NT 5.1, en-US, rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
		CURLOPT_URL=>html_entity_decode($url[1]),
		CURLOPT_FOLLOWLOCATION=> true,
		CURLOPT_SSL_VERIFYPEER=> false,
		CURLOPT_COOKIEJAR=> 'cookies.txt',
		CURLOPT_RETURNTRANSFER=> true));
	$res=curl_exec($c);
	preg_match('/var flashVars.+?({.+?})/',$res,$flashVars);
	$flashVarsParsed = json_decode($flashVars[1],true);
	$params=array();
	foreach(array('master_id','flashRevision','token','whatever','whoknows','exp','somethingelse') as $key) @$params[$key]=$flashVarsParsed[$key];
	file_put_contents('FBID_'.$params['master_id'].'/params.txt',implode(';',$params));
	file_put_contents('FBID_'.$params['master_id'].'/flashVars.txt',$res);
	file_put_contents('FBID_/params.txt',implode(';',$params));
	file_put_contents('FBID_/flashVars.txt',$res);
	if(count($cmd)<1) $cmd[]='php -c localphp.ini parser.php get_unit_list_lite '.$params['master_id'] . ' ' . $params['flashRevision'] . ' ' . $params['token'] . ' ' . 1 . "\n";
	$cmd[]='php -c localphp.ini parser.php arbeit_lite '.$params['master_id'] . ' ' . $params['flashRevision'] . ' ' . $params['token'] . ' ' . 1 . "\n";
}
if(@$argv[1]==='run') foreach($cmd as $c) system($c);
else foreach($cmd as $c) echo($c);
?>
