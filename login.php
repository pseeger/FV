<?php
include('config/accounts.php');
echo 'Found '.count($accounts)." accounts\r\n";
$cmd=array();
foreach($accounts as $login_email=>$login_pass) {
	echo 'Logging in '.$login_email."\r\n";
	$ch = curl_init();
	curl_setopt_array($ch,array(CURLOPT_URL=>'http://apps.facebook.com/onthefarm/index.php',
			CURLOPT_USERAGENT=> 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
			CURLOPT_FOLLOWLOCATION=>true,
			CURLOPT_SSL_VERIFYPEER=>false,
			CURLOPT_COOKIEJAR=>'/tmp/fblogincookies.txt',
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
	$refurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

	$c = curl_init();
	if(!preg_match('/<form [^>]*?flash.*?<\/form>/i',$res,$matches)) {
		echo "Couldn't login, maybe somethings broken?\n\rServer response was written to login_failure_".$login_email.".txt\n\r";
		file_put_contents('login_failure_'.$login_email.'.txt',$res);
		continue;
	}
	$xml = simplexml_load_string($matches[0]);
	$postdata = array();
	foreach($xml->input as $obj) {
		$attrs = $obj->attributes();
		if(isset($attrs->name) && isset($attrs->value)) $postdata[]=$attrs->name.'='.$attrs->value;
	}
	$url = $xml->attributes()->action;
	curl_setopt_array($c, array(CURLOPT_USERAGENT=> 'Mozilla/5.0 (Windows, U, Windows NT 5.1, en-US, rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3',
		CURLOPT_URL=>$url,
		CURLOPT_FOLLOWLOCATION=> true,
		CURLOPT_SSL_VERIFYPEER=> false,
		CURLOPT_COOKIEJAR=> '/tmp/fblogincookies.txt',
		CURLOPT_RETURNTRANSFER=> true,
		CURLOPT_POST=> 1,
		CURLOPT_REFERER => $refurl,
		CURLOPT_POSTFIELDS=>implode($postdata,'&')));
	$res=curl_exec($c);
	file_put_contents('/tmp/test',$res);
	preg_match('/var flashVars.+?({.+?})/',$res,$flashVars);
	$flashVarsParsed = json_decode($flashVars[1],true);
	$params=array();
	foreach(array('master_id','flashRevision','token','whatever','whoknows','exp','somethingelse') as $key) @$params[$key]=$flashVarsParsed[$key];
	$dirname = 'FBID_'.$params['master_id'];
	if(!is_dir($dirname)) mkdir($dirname);
	file_put_contents($dirname . '/params.txt',implode(';',$params));
	file_put_contents($dirname.'/flashVars.txt',$res);
	if(!is_dir('FBID_')) mkdir('FBID_');
	file_put_contents('FBID_/params.txt',implode(';',$params));
	file_put_contents('FBID_/flashVars.txt',$res);
	curl_close($ch);
	rename('/tmp/fblogincookies.txt',$dirname.'/cookies.txt');
	if(count($cmd)<1) $cmd[]='php -c localphp.ini parser.php get_unit_list_lite '.$params['master_id'] . ' ' . $params['flashRevision'] . ' ' . $params['token'] . ' ' . 1 . "\n";
	$cmd[]='php -c localphp.ini parser.php arbeit_lite '.$params['master_id'] . ' ' . $params['flashRevision'] . ' ' . $params['token'] . ' ' . 1 . "\n";
}
if(@$argv[1]==='run') foreach($cmd as $c) system($c);
else foreach($cmd as $c) echo($c);
?>
