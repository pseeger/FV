<?php
define('PX_VER_PARSER', '22120');
define('PX_DATE_PARSER', '2011-02-06');
define('PARSER_MAX_SPEED', '8');
define('PARSER_SQLITE', 'data.sqlite');
define('SCK_WRITE_PACKET_SIZE', 8192);
define('SCK_READ_PACKET_SIZE', 4096);

global $userId;
global $is_debug;
global $vDataDB;
global $plugin_developer;
global $use_proxy;
global $proxy_settings;
global $vCnt63000;
$GLOBALS['consolelog'] = true;

$use_proxy = false;

// load proxy settings
if (file_exists('proxy.txt')) {
	$proxy_settings = file('proxy.txt');
	if (count($proxy_settings))
		$use_proxy = true;
}

class Curlfetcher {
	private $ch;
	public function __construct() {
		$this->ch = curl_init();
		global $use_proxy;
		global $proxy_settings;
		curl_setopt_array($this->ch,array(
		CURLOPT_USERAGENT=> 'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10',
		CURLOPT_FOLLOWLOCATION=>true,
		CURLOPT_SSL_VERIFYPEER=>false,
		CURLOPT_RETURNTRANSFER=>true,
		CURLOPT_AUTOREFERER=>true,
		CURLOPT_HEADER=>false,
		CURLOPT_ENCODING=>'gzip'));
		$proxy_settings = $GLOBALS['proxy_settings'];
		if($GLOBALS['use_proxy']) curl_setopt($this->ch, CURLOPT_PROXY, $proxy_settings[0].':'.$proxy_settings[1]);
		if(count($proxy_settings)>2) curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $proxy_settings[2].':'.$proxy_settings[3]);
	}

	public function get($url) {
		curl_setopt($this->ch, CURLOPT_URL, $url);
		$res = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE)>=400) {
			AddLog2(curl_getinfo($this->ch, CURLINFO_HTTP_CODE)."\r\n");
			return 0;
		}
		if(curl_getinfo($this->ch,CURLINFO_CONTENT_TYPE)=='application/x-gzip') return gzuncompress($res);
		else return $res;
	}

	public function post($url, $postdata='', $contentType='') {
		curl_setopt_array( $this->ch, array(
		CURLOPT_URL=>$url,
		CURLOPT_POST=> true,
		CURLOPT_POSTFIELDS => $postdata,
		CURLOPT_HTTPHEADER => array('Content-Length: '.strlen($postdata),'Content-Type: '.$contentType)
		));
		$res = curl_exec($this->ch);
		//Reset the state
		curl_setopt_array( $this->ch, array(CURLOPT_POSTFIELDS => '',CURLOPT_HTTPHEADER => array()));
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE)>=400) {
			AddLog2(curl_getinfo($this->ch, CURLINFO_HTTP_CODE)."\r\n");
			return 0;
		}
		return $res;
	}
}
if(function_exists('curl_exec')) $GLOBALS['curlfetcher'] = new Curlfetcher();

// Set to 1 if you want to load plugins like pre v1.10, good for debuging plugin code
// Otherwise keep 0 and we wont load plugins when they arent needed
$plugin_developer = 0;
$is_debug = false;

error_reporting(E_ALL);
ini_set('display_errors', true);

$tmp = pack("d", 1); // determine the multi-byte ordering of this machine temporarily pack 1
define("AMFPHP_BIG_ENDIAN", $tmp == "\0\0\0\0\0\0\360\77");
$GLOBALS['amfphp']['encoding'] = 'amf3';

define('AMFPHP_BASE', 'amfphp/core/');

require_once(AMFPHP_BASE . "shared/util/CharsetHandler.php");
require_once(AMFPHP_BASE . "amf/util/AMFObject.php");
require_once(AMFPHP_BASE . "shared/util/CompatPhp5.php");

set_time_limit(0);

if (version_compare(phpversion(), '5.0.0', '<')) {
	define('STDIN', fopen('php://stdin', 'r'));
	define('STDOUT', fopen('php://stdout', 'w'));
}

$timezonefile = './timezone.txt';
if (file_exists($timezonefile)) {
	$timezone = trim(file_get_contents($timezonefile));
	if (strlen($timezone) > 2) {
		date_default_timezone_set($timezone);
	}
} else {
	@date_default_timezone_set('America/Los_Angeles');
}

if($is_debug)
  @error_log(print_r($GLOBALS['argv'],true));

# connect
$vDataDB=Parser_SQlite_Connect(PARSER_SQLITE);

$Load_Farm_Read_Size = 0; //0 = Read all, any other number sets the size read

// ------------------------------------------------------------------------------
// GetData gets data from http $answer
//  @param string $answer http answer
//  @return string data
// ------------------------------------------------------------------------------
function GetData($answer) {
	$pos = strpos($answer, "\r\n\r\n");
	if ($pos !== false) {
		return substr($answer, $pos + 4, strlen($answer));
	} else
		return null;
}
// ------------------------------------------------------------------------------
// CreateRequestAMF creates AMF object
//  @param string $request
//  @param string $function
//  @return object AMF object
// ------------------------------------------------------------------------------
function CreateRequestAMF($request = '', $function = '') {
	$amf = new AMFObject("");
	$amf->_bodys[0] = new MessageBody();

	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';

	$amf->_bodys[0]->_value[0] = GetAMFHeaders();

	$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();

	$amf->_bodys[0]->_value[1][0]['params'] = array();
	if ($request)
		$amf->_bodys[0]->_value[1][0]['params'][0] = $request;

	if ($function)
		$amf->_bodys[0]->_value[1][0]['functionName'] = $function;

	$amf->_bodys[0]->_value[2] = 0;

	return $amf;
}
// ------------------------------------------------------------------------------
// RaiseError raise error
//  @param string $errnum
// ------------------------------------------------------------------------------
function RaiseError($errnum) {
	EchoData(sprintf('_Error : %08X', $errnum));
}
// ------------------------------------------------------------------------------
// RestartBot sends command to restart
// ------------------------------------------------------------------------------
function RestartBot() {
	global $userId;
	echo "\n Restarting Bot in 15 seconds\n";
	sleep(15);
	touch($userId.'_need_restart.txt'); //creating this file will cause the game to restart
	die;
}
// ------------------------------------------------------------------------------
// GetAMFHeaders
//  @return array auth parameters
// ------------------------------------------------------------------------------
function GetAMFHeaders() {
	global $userId, $flashRevision, $token;
	LoadAuthParams();
	return array(
	'sigTime'=>time().'.0000',
	'token'=>$token,
	'flashRevision'=>$flashRevision,
	'masterId'=>$userId,
	'wid'=>0,
	'snId'=>1);
}
// ------------------------------------------------------------------------------
// LoadAuthParams
// ------------------------------------------------------------------------------
function LoadAuthParams() {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
}
// ------------------------------------------------------------------------------
// SaveAuthParam
// ------------------------------------------------------------------------------
function SaveAuthParams() {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	file_put_contents(F('params.txt'),implode(';', array($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy)));
}
// ------------------------------------------------------------------------------
// GetSequense
// ------------------------------------------------------------------------------
function GetSequense() {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	LoadAuthParams();
	$sequence ++;
	SaveAuthParams();
	return $sequence;
}
// ------------------------------------------------------------------------------
// SetSequense
// ------------------------------------------------------------------------------
function SetSequense($new_sequence) {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	LoadAuthParams();
	$sequence = $new_sequence;
	SaveAuthParams();
}
// *********************************************************************
// we save lots of things now so created this to cleaning up the code *
// *********************************************************************
function save_botarray ($array, $filename) {
	file_put_contents($filename,serialize($array));
}
// ********
// These functions might fix our problem with big farms
// ********
function fullwrite ($sd, $buf) {
	$total = 0;
	$len = strlen($buf);

	while ($total < $len && ($written = fwrite($sd, $buf))) {
		$total += $written;
		$buf = substr($buf, $written);
	}

	return $total;
}

function fullread ($sd, $len) {
	$ret = '';
	$read = 0;

	while ($read < $len && ($buf = fread($sd, $len - $read))) {
		$read += strlen($buf);
		$ret .= $buf;
	}

	return $ret;
}
// ------------------------------------------------------------------------------
// Connect connects to farmville server
//  @return resourse socket connection
// ------------------------------------------------------------------------------
function Connect($server = '') {
	global $use_proxy;
	global $proxy_settings;

	if (!$server) $server = farmer;

	if ($use_proxy) $s = fsockopen(trim($proxy_settings[0]), intval($proxy_settings[1]));
	else $s = fsockopen($server, 80);

	if (!$s) {
		RaiseError(3);
		exit;
	}
	return $s;
}
// ------------------------------------------------------------------------------
// EchoData returns data in the main application
//  @param string $data data
// ------------------------------------------------------------------------------
function EchoData($data) {
	echo 'Echo Data called';
	file_put_contents('out.txt',$data);
}
// ------------------------------------------------------------------------------
// proxy_GET can use a proxy to get the gameSettings/description xml files
// ------------------------------------------------------------------------------
function proxy_GET($url) {
	global $use_proxy;
	global $proxy_settings;
	global $Load_Farm_Read_Size;
	$p = parse_url($url);
	$Load_Farm_Read_Size = 0;
	$headers = array();
	$headers[] = 'GET ' . $url . ' HTTP/1.1';
	$headers[] = 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; GTB6.5; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET4.0C)';
	$headers[] = 'Host: ' . (($p['host'])?$p['host']:farmer);
	$headers[] = 'Accept: */*';
	$headers[] = 'Accept-Encoding: gzip';

	if ($use_proxy) {
		if (isset($proxy_settings[2]) && isset($proxy_settings[3])) { // is set proxy user and password
			$authorization = base64_encode(trim($proxy_settings[2]) . ':' . trim($proxy_settings[3]));
			$headers[] = "Proxy-Authorization: Basic $authorization";
		}
	}
	$s = Connect(@$p['host']);
	stream_set_blocking($s, 0);

	$request = implode("\r\n", $headers);
	$request .= "\r\n\r\n";

	fullwrite($s, $request);

	$answer = '';
	// wait max 50 seconds for data
	$max_tick = 500;

	$cur_tick = 0;
	$is_bad = false;

	while (!strlen($answer)) {
		$answer .= fullread($s, 1024);

		if (!strlen($answer)) {
			usleep(100000);
			$cur_tick ++;

			if ($cur_tick > $max_tick) {
				$is_bad = true;
				break;
			}
		}
	}

	if ($is_bad) {
		fclose($s);
		AddLog2("HTTP Timeout: no answer");
		return 0;
	}

	if (strpos($answer, '404 Not Found') !== false) {
		fclose($s);
		AddLog2("HTTP Error 404: Not Found ($url)");
		return 0;
	}

	if (strpos($answer, '500 Internal Server Error') !== false) {
		AddLog2("HTTP Error 500: Internal Server Error ($url)");
		fclose($s);
		return 0;
	}

	preg_match('/Content-Type:[\s](.*?)[\s]/si', $answer, $matchzip);

	preg_match('/Content-length:[\s]([0-9]*)[\s]/si', $answer, $match);
	$answer = GetData($answer);

	while (true) {
		if ($Load_Farm_Read_Size == 0) {
			$Load_Farm_Read_Size = $match[1];
		}
		if ($Load_Farm_Read_Size == 0) {
			echo "\n*****\n";
			echo '(Proxy GET) Read: ' . strlen($answer) . ' wanted: ' . $match[1] . ' wtf?';
			die("\n\nERROR: Lost connection to server\n*****\n\n");
		}

		$answer .= fullread($s, $Load_Farm_Read_Size);

		if (strlen($answer) >= $match[1])
			break;
		usleep(100000);
	}
	if ($Load_Farm_Read_Size == $match[1]) {
		$Load_Farm_Read_Size = 0;
	}

	if (@$matchzip[1] == "application/x-gzip") {
		unset ($matchzip);
#		return gzuncompress(substr(base64_decode($answer), 3));
		return gzuncompress($answer);
	} else {
		return $answer;
	}
}
// ------------------------------------------------------------------------------
// proxy_GET_FB can use a proxy to get FB-Sites
// ------------------------------------------------------------------------------
function proxy_GET_FB($url, $vPostGet='GET', $vPostData='') {

}
function proxy_GET_FB_old($url, $vPostGet='GET', $vPostData='') {	
	global $use_proxy;
	global $proxy_settings;
	global $Cnt302;

	// Header
	$headers = array();
	$p = parse_url($url);
	$headers[] = $vPostGet.' '.$p['path'].'?'.$p['query'].' HTTP/1.1';
	$headers[] = 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; GTB6.5; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET4.0C)';
	$headers[] = 'Host: '.$p['host'];
	$headers[] = 'Accept: */*';
	$headers[] = 'Connection: Close';
	$headers[] = 'Cookie: '.Parser_GetCookieString();
	if($vPostGet=='POST') $headers[] = 'Content-Length: '.strlen($vPostData);
	$headers[] = 'Pragma: no-cache';
	$headers[] = 'Accept-Language: en-US';
	$px_Setopts = LoadSavedSettings();
	if (@$px_Setopts['e_gzip']) {
#		$headers[] = 'Accept-Encoding: gzip';
	}

	if ($use_proxy) {
		if (isset($proxy_settings[2]) && isset($proxy_settings[3])) { // is set proxy user and password
			$authorization = base64_encode(trim($proxy_settings[2]) . ':' . trim($proxy_settings[3]));
			$headers[] = "Proxy-Authorization: Basic $authorization";
		}
	}

	$request = implode("\r\n", $headers);
	$request .= "\r\n\r\n";
	if($vPostGet=='POST') $request .= $vPostData;

	if ($use_proxy)
		$s = fsockopen(trim($proxy_settings[0]), intval($proxy_settings[1]));
	else
		$s = fsockopen('apps.facebook.com', 80);
	if (!$s) {
		RaiseError(3);
		exit;
	}
	stream_set_blocking($s, 0);
	fullwrite($s, $request);
	$vHTTPResponse = '';
	// wait max 10 seconds for data
	$max_tick = 100;
	$cur_tick = 0;
	$is_bad = false;

	while (!feof($s)){
		$vHTTPResponse .= fgets($s, 1024);
		if (!strlen($vHTTPResponse)){
			usleep(100000);
			$cur_tick ++;
			if ($cur_tick > $max_tick){
				$is_bad = true;
				break;
			}
		}
	}
	fclose($s);
	if ($is_bad){
		AddLog2("proxy_GET_FB: XML-proxy: -no answer-");
		return 0;
	}

	if (strpos($vHTTPResponse, 'login.php') !== false){
		AddLog2("proxy_GET_FB: not logged in? Check cookie-settings");
	}

	if (strpos($vHTTPResponse, '404 Not Found') !== false){
		AddLog2("proxy_GET_FB: Error 404");
	}

	if (strpos($vHTTPResponse, '500 Internal Server Error') !== false) {
		AddLog2("proxy_GET_FB: Error 500/ISE");
	}

	if (strpos($vHTTPResponse,'Content-Encoding: gzip')!==false) {
		return(gzinflate(substr(GetData($vHTTPResponse),10)));
	} else {
		return(GetData($vHTTPResponse));
	}
}

// ------------------------------------------------------------------------------
// Request sends AMF request to the farmville server
//  @param resourse $s socket connection
//  @param string $result AMF-serialized request
//  @return string http answer
// ------------------------------------------------------------------------------
function Request($s, $result) {
	echo 'Request called';
	global $use_proxy;
	global $proxy_settings;
	global $Load_Farm_Read_Size;

	$headers = array();
	$headers[] = 'POST ' . farmer_url . ' HTTP/1.1';
	$headers[] = 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; GTB6.5; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET4.0C)';
	$headers[] = 'Host: ' . farmer;
	$headers[] = 'Content-Type: application/x-amf';
	// enable gzip
	$px_Setopts = LoadSavedSettings();
	if (@$px_Setopts['e_gzip']) {
		$headers[] = 'Accept-Encoding: gzip';
	}

	if ($use_proxy) {
		if (isset($proxy_settings[2]) && isset($proxy_settings[3])) { // is set proxy user and password
			$authorization = base64_encode(trim($proxy_settings[2]) . ':' . trim($proxy_settings[3]));
			$headers[] = "Proxy-Authorization: Basic $authorization";
		}
	}

	$headers[] = 'Accept: */*';
	$headers[] = 'Content-Length: ' . strlen($result);

	stream_set_blocking($s, 0);

	$request = implode("\r\n", $headers);
	$request .= "\r\n\r\n" . $result;

	fullwrite($s, $request);

	$answer = '';
	// wait max 50 seconds for data before we repeat request
	$max_tick = 500;

	$cur_tick = 0;
	$is_bad = false;

	while (!strlen($answer)) {
		$answer .= fullread($s, 1024);

		if (!strlen($answer)) {
			usleep(100000);
			$cur_tick ++;

			if ($cur_tick > $max_tick) {
				$is_bad = true;
				break;
			}
		}
	}

	if ($is_bad) {
		fclose($s);
		$s = Connect();
		AddLog2("repeat request -no answer-");
		return Request($s, $result);
	}

	if (strpos($answer, '404 Not Found') !== false) {
		fclose($s);
		$s = Connect();
		AddLog2("repeat request -404-");
		return Request($s, $result);
	}

	if (strripos($answer, '500 Internal Server Error') !== false) {
		AddLog2("Error: 500/ISE");
		echo "\n*****\nERROR: Internal Server Error\nIf you get this message constantly and farmville appears to be working fine in your browser\nYou have too many objects/superplots. See the Farm Problems section of the parser forum page\n*****\n";
		fclose($s);
		return 0;
	}

	if (strripos($answer, '502 Bad Gateway') !== false) {
		AddLog2("Error: 502/BADGY");
		echo "\n*****\nERROR: 502 Bad Gateway\nYou can likely ignore this error unless it happens constantly\n*****\n";
		return 0;
	}

	preg_match('/Content-Encoding:[\s]([a-zA-Z]*)[\s]/si', $answer, $matchzip);

	preg_match('/Content-length:[\s]([0-9]*)[\s]/si', $answer, $match);
	$answer = GetData($answer);

	while (true) {
		if ($Load_Farm_Read_Size == 0) {
			$Load_Farm_Read_Size = @$match[1];
		}
		if ($Load_Farm_Read_Size == 0) {
			echo "\n*******\n";
			echo 'Debug: Read ' . strlen($answer) . ' wanted: ' . @$match[1] . ' Answer: ' . $answer . '';
			die("\nERROR: Lost connection to server\n*******\n\n");
		}

		$answer .= fullread($s, $Load_Farm_Read_Size);

		if (strlen($answer) >= $match[1])
			break;
		usleep(100000);
	}
	if ($Load_Farm_Read_Size == $match[1]) {
		$Load_Farm_Read_Size = 0;
	}

	if (@$matchzip[1] == 'gzip') {
		unset ($matchzip);
		return gzinflate(substr($answer, 10));
	} else {
		return $answer;
	}
}
// ------------------------------------------------------------------------------
// GetFarmserver returns farmville server name
//  @return string Server name
// ------------------------------------------------------------------------------
function GetFarmserver() {
	@list($res, $res2) = @explode(';', trim(@file_get_contents('farmserver.txt')));
	if (empty($res)) {
		$flashVars = parse_flashvars();
		$app_url = (@$flashVars['app_url'])?$flashVars['app_url']:'http://fb-ak-0.farmville.com/';
		preg_match('/http:\/\/(.*?)\//', $app_url, $match);
		$res = $match[1];
		echo "Farmserver $res;\n";
	}

	return $res;
}
// ------------------------------------------------------------------------------
// GetFarmUrl returns farmville URL
//  @return string URL
// ------------------------------------------------------------------------------
function GetFarmUrl() {
	@list($res2, $res) = @explode(';', trim(@file_get_contents('farmserver.txt')));
	if (empty($res)) {
		$flashVars = parse_flashvars();
		$app_url = (@$flashVars['app_url'])?$flashVars['app_url']:'http://fb-ak-0.farmville.com/';
		$res = $app_url.'flashservices/gateway.php';
		echo "FarmUrl $res;\n";
	}

	return $res;
}
// ------------------------------------------------------------------------------
// DoInit Loading farms
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function DoInit() {
	AddLog2("Init user. Load Farm");
	$T = time(true);
	$res = 0;

	Hook('before_load_farm');

	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	LoadAuthParams();
	SetSequense(0);

	// Create Init request
	$amf = CreateRequestAMF('', 'UserService.initUser');
	$amf->_bodys[0]->_value[1][0]['params'][0] = "";
	$amf->_bodys[0]->_value[1][0]['params'][1] = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2] = true;

	$amf2=RequestAMFIntern($amf);
	$res=CheckAMF2Response($amf2);
	if ($res == 'OK') {

		LoadAuthParams();
		// get flashSessionKey
		$sequence = 1;
		if (isset($amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['flashSessionKey'])) {
			$flashSessionKey = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['flashSessionKey'];
		}
		// save to file $flashSessionKey, $xp, $energy
		$xp = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['xp'];
		$energy = $amf2->_bodys[0]->_value['data'][0]['data']['energy'];
		SaveAuthParams();
		// get extra info
		$level = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['level'];
		$gold = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['gold'];
		$cash = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['cash'];
		$sizeX = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['sizeX'];
		$sizeY = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['sizeY'];
		$firstname = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['attr']['name'];
		$locale = $amf2->_bodys[0]->_value['data'][0]['data']['locale'];
		// save to file $level, $coins, $cash, $sizex, $sizey
		file_put_contents(F('playerinfo.txt'),implode(';', array($level, $gold, $cash, $sizeX, $sizeY, $firstname, $locale)));

		// save world to file
		save_botarray ($amf2->_bodys[0]->_value, F('world.txt'));

		$amf2->rawData='';
		$f = fopen(F('world_'.$firstname.'.txt'), "w+");
		fwrite($f, print_r($amf2,true));
		fclose($f);

		// get objects on farm
		$objects = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['objectsArray'];
		// FarmFIX/object split
		$my_farm_is_fucked_up = 0;
		if (file_exists('farmfix.txt')) {
			$my_farm_is_fucked_up = trim(file_get_contents('farmfix.txt'));
		}

		if ($my_farm_is_fucked_up == 1) {
			$obj_total = count($objects);

			if ($obj_total <= 800) {
				AddLog2("You should turn off Object Split if your farm works without it.");
			}

			if ($obj_total > 1) {
				// 1ST FILE
				for ($i = 0; $i <= round($obj_total / 2); $i++) {
					$obj_split_one[] = $objects[$i];
				}
				save_botarray ($obj_split_one, F('objects_1.txt'));
				// end 1ST FILE
				// 2nd FILE
				for ($i = round($obj_total / 2) + 1; $i < $obj_total; $i++) {
					$obj_split_two[] = $objects[$i];
				}
				save_botarray ($obj_split_two, F('objects_2.txt'));
				// end 2nd FILE
			}
		} //FarmFIX
		else {
			// save objects to file
			save_botarray ($objects, F('objects.txt'));
		}
		// save collection counters to a file
		$c_count = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['collectionCounters'];
		save_botarray ($c_count, F('ccount.txt'));
		// save rewards to a file
		$rewardlinks = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['friendRewards'];
		save_botarray ($rewardlinks, F('rlinks.txt'));
		// save lonelyanimals to a file
		$animallinks = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['lonelyAnimals'];
		save_botarray ($animallinks, F('ralinks.txt'));
		// save giftbox info for plugins
		$ingiftbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-1'];
		foreach ($ingiftbox as $key => $item)
			$ingiftbox[$key] = isset($item[0])?$item[0]:0;
		save_botarray ($ingiftbox, F('ingiftbox.txt'));
		// save consumable info for plugins
		$inconbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-6'];
		foreach ($inconbox as $key => $item)
			$inconbox[$key] = isset($item[0])?$item[0]:0;
		save_botarray ($inconbox, F('inconbox.txt'));
		// save storage info for plugins
		$instorage = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-2'];
		foreach ($instorage as $key => $item)
			$instorage[$key] = $item[0];
		save_botarray ($instorage, F('instorage.txt'));
		// save neighbors list
		$neighbors = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighbors'];
		save_botarray ($neighbors, F('neighbors.txt'));
		// save crop mastery list
		$px_cropmastery = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['masteryCounters'];
		save_botarray ($px_cropmastery, F('cropmastery.txt'));
		// save crop mastery count
		$cropmasterycount = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['mastery'];
		save_botarray ($cropmasterycount, F('cropmasterycount.txt'));
		// save feature credits
		$featurecredits = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['featureCredits'];
		save_botarray ($featurecredits, F('featurecredits.txt'));

		// save ribbon data
		$px_achievements = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['achCounters'];
		$earned_ribbons = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['achievements'];

		$ribbon_merge = array();
		foreach($px_achievements as $name => $data) {
			$ribbon_merge[$name]['count'] = $data;
		}

		if (@count($earned_ribbons) > 0) {
			foreach($earned_ribbons as $name => $data) {
				$ribbon_merge[$name]['earned'] = $data;
			}
		}
		save_botarray($ribbon_merge, F('ach_count.txt'));
		// save_botarray ($array, $filename);
	}

	Hook('after_load_farm');

	AddLog2("result $res");
	$T2 = time();
	$T2 -= $T;
	AddLog2("Init Time: " . $T2 . " Seconds");

	global $vCnt63000;
	$vCnt63000=63000;

	return $res;
}
// ------------------------------------------------------------------------------
// Arbeit Perform work on the farm
// ------------------------------------------------------------------------------
function Arbeit() {
	global $settings;
	global $need_reload;

	global $px_Setops;

	global $enable_harvest, $enable_harvest_animal, $enable_harvest_tree, $enable_seed, $enable_hoe, $enable_combine;
	global $enable_biplane, $enable_harvest_arborist, $enable_harvest_arborist_at, $enable_harvest_farmhands, $enable_harvest_farmhands_at;
	global $enable_harvest_spec;
	global $userId, $flashRevision, $botlitever;

	global $res_str;
	global $plugin_developer;

	if(isset($botlitever)) {
		$argv = @$GLOBALS['argv'];
		$userId = @$argv[2];
		$flashRevision = @$argv[3];
	} else {
		$datasize = hexdec(fread(STDIN, 8));
		$data = fread(STDIN, $datasize);
		list($userId, $flashRevision) = explode(';', $data);
	}

	define ('farmer', GetFarmserver());
	define ('farmer_url', GetFarmUrl());

	if (@$flashRevision == '' || @$userId == '' || @$flashRevision == 'null' || @$userId == 'null' || @$flashRevision == 'reload' || @$userId == 'reload') {
		echo "Flash Revision unknown. A plugin likely has an error\r\n";
		AddLog2("Flash Revision unknown.\r\n A plugin likely has an error");
		RestartBot();
		return;
	}

	if (!$plugin_developer) {
		echo "##### Loading plugins #####\r\n";
		pluginload();
	}

	if(is_file(LogF("log2.txt"))) {
		$vDir='./farmville-logs';
		if (!is_dir($vDir)) {
			@mkdir($vDir);
		}
		copy(LogF("log2.txt"),'./farmville-logs/'.$userId.'_log2_'.date('Ymd_His').'.log');

	}

	#// clear advanced log
	$f = fopen(LogF("log2.txt"), "w+");
	fclose($f);

	while(file_exists('notrun_parser.txt') || file_exists('notrun_parser_'.$userId.'.txt')) {
		AddLog2("Bot Paused. Next check in 30 seconds.");
		sleep(30);
	}

	AddLog2("start");

	Hook('before_work');

	// Init
	$res = DoInit();
	if ($res != 'OK') {
		RaiseError(2);
	} else {
		$res_str = ''; //for main logs
	}

	Hook('before_load_settings');

	// load settings
	if (!function_exists('LoadSavedSettings')) {
		die("\n\nSettings plugin installed incorrectly no LoadSavedSettings found!\n\n");
	}

	$px_Setopts = LoadSavedSettings();
	$enable_harvest = @$px_Setopts['e_harvest']; //harvest crops
	$enable_biplane = @$px_Setopts['e_biplane'];
	$enable_harvest_animal = @$px_Setopts['e_h_animal']; //get product from livestock
	$enable_harvest_tree = @$px_Setopts['e_h_tree']; //harvest from trees
	$enable_seed = @$px_Setopts['e_seed']; //planting
	$keep_seed = @$px_Setopts['e_seed_keep']; //seed_keep
	$enable_hoe = @$px_Setopts['e_hoe']; //hoe
	$enable_combine = @$px_Setopts['e_combine']; //hoe
	$enable_harvest_building = @$px_Setopts['e_h_building']; //harvest buildings
	$enable_harvest_building_coop = @$px_Setopts['e_h_building_coop'];
	$enable_harvest_building_dairy = @$px_Setopts['e_h_building_dairy'];
	$enable_harvest_building_horse = @$px_Setopts['e_h_building_horse'];
	$enable_harvest_building_nursery = @$px_Setopts['e_h_building_nursery'];
	$enable_harvest_building_bees = @$px_Setopts['e_h_building_bees'];
	$enable_harvest_building_pigs = @$px_Setopts['e_h_building_pigs'];
	$enable_harvest_building_hauntedhouse = @$px_Setopts['e_h_building_hauntedhouse'];
	$enable_harvest_building_trough = @$px_Setopts['e_h_building_trough'];
	$enable_harvest_building_orchard = @$px_Setopts['e_h_building_orchard'];
	$enable_harvest_building_turkeyroost = @$px_Setopts['e_h_building_turkeyroost'];
	$enable_harvest_building_wworkshop = @$px_Setopts['e_h_building_wworkshop'];
	$enable_harvest_building_snowman = @$px_Setopts['e_h_building_snowman'];
	$enable_harvest_building_snowman = @$px_Setopts['e_h_building_duckpond'];
	$enable_harvest_building_snowman = @$px_Setopts['e_h_building_ccastle'];
	$enable_harvest_arborist = @$px_Setopts['e_h_arborist'];
	$enable_harvest_arborist_at = @$px_Setopts['e_h_arborist_at'];
	$enable_harvest_arborist_min = @$px_Setopts['e_h_arborist_min'];
	$enable_harvest_farmhands = @$px_Setopts['e_h_farmhands'];
	$enable_harvest_farmhands_at = @$px_Setopts['e_h_farmhands_at'];
	$enable_harvest_farmhands_min = @$px_Setopts['e_h_farmhands_min'];
	$enable_lonlyanimals = @$px_Setopts['lonlyanimals'];
	$enable_wanderinganimals = @$px_Setopts['wanderinganimals'];
	$enable_harvest_spec = $px_Setopts['e_harvest_spec'];	  //   harvest ONLY specific crops?
	$spec_crop = $px_Setopts['spec_crop'];				  //   which specific crop?
	$spec_crop_quantity = $px_Setopts['spec_crop_quantity'];   //   specific crop quantity
	$enable_acceptneighborhelp = @$px_Setopts['acceptneighborhelp'];
	$enable_acceptgifts = @$px_Setopts['acceptgifts'];
	$enable_acceptgifts_sendback = @$px_Setopts['acceptgifts_sendback'];
	$enable_acceptgifts_twice = @$px_Setopts['acceptgifts_twice'];
	$enable_acceptgifts_num = @$px_Setopts['acceptgifts_num'];
	if(strlen($enable_acceptgifts_num)==0) $enable_acceptgifts_num=10;

	if($enable_acceptgifts) {

		$vGiftReqs=Parser_ReadReq();
		save_botarray($vGiftReqs, F('gift_reqs.txt'));
		AddLog2('Parser_gift_reqs: '.count($vGiftReqs).' to accept');
		if(is_array($vGiftReqs)) {
			if(count($vGiftReqs)>0) {
				if($enable_acceptgifts_twice) $vGiftReqs=array_merge($vGiftReqs,$vGiftReqs);
				$vGCount=0;
				foreach($vGiftReqs as $vI => $vData) {
					if($vGCount>=$enable_acceptgifts_num) break;
					$vGCount++;
					$vWhat=explode('&',str_replace(array('?','='),array('&','&'),$vData['action_url']));
					AddLog2('Parser_gift_reqs: '.$vGCount.' accept '.Units_GetRealnameByName($vWhat[4]).' ('.$vWhat[4].') from '.$vWhat[2]);
					error_log('"'.$vWhat[2].'";"'.$vWhat[4].'";"'.date('Y.m.d H:i:s').'"'."\n",3,LogF('gifts_accepted.csv'));

					$vResponse = proxy_GET_FB("http://www.facebook.com/ajax/reqs.php?__a=1", 'POST', $vData['post_data']);


					$vResponse = proxy_GET_FB($vData['action_url']);
					if (!empty($vResponse)) {
						AddLog2('Parser_gift_reqs: '.$vGCount.' accept - Success');
					} else {
						AddLog2('Parser_gift_reqs: '.$vGCount.' accept - Failed');
					}

					if ($enable_acceptgifts_sendback) {

						preg_match_all('/<form.*?action="(.*?)".*?<\/form>/ims', $vResponse, $vTYForms);


						foreach($vTYForms[0] as $vJ => $vTYForm) {
							if(stripos($vTYForm, 'thank you') !== false || stripos($vTYForm, 'send to') !== false) {

								AddLog2('Parser_gift_reqs: send thankyou-gift '.Units_GetRealnameByName($vWhat[4]).' ('.$vWhat[4].') to '.$vWhat[2]);
								error_log('"'.$vWhat[2].'";"'.$vWhat[4].'";"'.date('Y.m.d H:i:s').'"'."\n",3,LogF('gifts_send_thankyou.csv'));

								preg_match_all('/.*action="([^"]*)".*/ims', $vTYForm, $vAction);

								preg_match_all('/.*giftRecipient=([^&]*).*type="([^"]*)".*content="([^"]*)".*id="([^"]*)".*post_form_id=([^&]*).*/ims', $vTYForm, $vTYFields);


								$vPostData='app_id=102452128776&to_ids[0]='.$vTYFields[1][0].'&request_type='.urlencode($vTYFields[2][0]).'&invite=false&content='.urlencode(html_entity_decode($vTYFields[3][0])).'&preview=true&is_multi=false&is_in_canvas=true&form_id='.$vTYFields[4][0].'&prefill=true&message=&donot_send=false&include_ci=false&__d=1&post_form_id='.$vTYFields[5][0].'&fb_dtsg='.$vData['fb_dtsg'].'&lsd&post_form_id_source=AsyncRequest';

								$vResponse2 = proxy_GET_FB("http://www.facebook.com/fbml/ajax/prompt_send.php?__a=1", 'POST', $vPostData);


								#$vPostData=str_replace('&request_type=','&&request_type=',$vPostData);
								$vPostData=str_replace('&preview=true&','&preview=false&',$vPostData);
								$vResponse3 = proxy_GET_FB("http://www.facebook.com/fbml/ajax/prompt_send.php?__a=1", 'POST', $vPostData);
								if (stripos(strip_tags($vResponse3),'"error":0')) {
									AddLog2('Parser_gift_reqs: send thankyou-gift - Success');
								} else {
									AddLog2('Parser_gift_reqs: send thankyou-gift - Failed');
								}


								$vResponse4 = proxy_GET_FB(html_entity_decode($vAction[1][0]), 'POST', '');


								#break;
								unset($vResponse2,$vResponse3,$vResponse4);
							}
						}
					}
					unset($vResponse);
				}
			}
		}
	}

	Hook('after_load_settings');

	$need_reload = false;

	if ($enable_lonlyanimals) {
		AddLog2("check lonlyanimal");
		Do_Check_Lonlyanimals();
	}
	if ($enable_wanderinganimals) {
		AddLog2("check wanderinganimals");
		Do_Check_Wanderinganimals();
	}

	if ($enable_acceptneighborhelp) {
		AddLog2("accept neighbor help");
		Do_Accept_Neighbor_Help();
	}

	if ($enable_biplane) {
		AddLog2("biplane instantgrow");

		$plot_list = GetObjects('Plot'); //get plots
		$cntplots = 0;
		foreach($plot_list as $plot) {
			if (($plot['state'] == 'planted'))
				$cntplots ++;
		}
		unset($plot_list);

		if ($cntplots > 0) {
			Do_Biplane_Instantgrow();
			DoInit(); //reload farm
			$need_reload = false;
		}
		unset($cntplots);
	}

	Hook('before_harvest');

	if ($enable_harvest) {
		AddLog2("harvest crops");

		$plot_list = GetObjects('Plot'); //get plots
		$plots = array();
		switch($enable_harvest_spec){
			//   harvest specific only
			case 1:
				AddLog2('n0m mod: Harvest only ' . $spec_crop_quantity . ' of ' . $spec_crop . ' crops!');
				$iPlotCount = 0;
		foreach($plot_list as $plot) {
					//   if the crop is ready
					if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe')){
						//   if that's a specified crop
						if ($plot['itemName'] == $spec_crop){
							//   if limit not assigned OR not yet reached
							if(($spec_crop_quantity == 0) || ($iPlotCount < $spec_crop_quantity)){
								$plots[] = $plot;
								$iPlotCount++;
							}
						}
					}
				}

				AddLog2('Ready plots: ' . count($plots));
			break;
			//   harvest all
			default:
				foreach($plot_list as $plot) {
					if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe')){
				$plots[] = $plot;
		}
				}
				AddLog2('n0m mod: Harvest ALL ( ' . count($plots) . ' ) crops!');
			break;
		}

		unset($plot_list);

		if (count($plots) > 0)
			Do_Farm_Work_Plots($plots, 'harvest'); //harvest land
		unset($plots);
	}

	Hook('after_harvest');

	Hook('before_harvest_buildings');

	if ($enable_harvest_building == 1) {
		AddLog2("harvest buildings");

		$building_list = array();

		if ($enable_harvest_building_dairy == 1) {
			$x = GetObjects("DairyFarmBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_coop == 1) {
			$x = GetObjects("ChickenCoopBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_horse == 1) {
			$x = GetObjects("HorseStableBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);}
		if ($enable_harvest_building_nursery == 1) {
			$x = GetObjects("NurseryBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_bees == 1) {
			$x = GetObjects("BeehiveBuilding");
			if (is_array($x))
			$building_list = array_merge($building_list, $x);
		}

		if ($enable_harvest_building_pigs == 1) {
			$x = GetObjects("PigpenBuilding");
			if (is_array($x))
			$building_list = array_merge($building_list, $x);
		}

		if ($enable_harvest_building_hauntedhouse == 1) {
			$x = GetObjects("HalloweenHauntedHouseBuilding");
			if (is_array($x))
			$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_trough == 1) {
			$x = GetObjects("FeedTroughBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_orchard == 1) {
			$x = GetObjects("OrchardBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_turkeyroost == 1) {
			$x = GetObjects("TurkeyRoostBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if ($enable_harvest_building_wworkshop == 1 || $enable_harvest_building_snowman == 1 || $enable_harvest_building_ccastle == 1  || $enable_harvest_building_duckpond == 1) {
			$x = GetObjects("FeatureBuilding");
			if (is_array($x))
				$building_list = array_merge($building_list, $x);
		}
		if (count($building_list) > 0) {
			$buildings = array();
			foreach($building_list as $plot) {
				if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') || ($plot['m_hasAnimal'] == 1))
					$buildings[] = $plot;
			}
			if (count($buildings) > 0)
				Do_Farm_Work($buildings); //harvest buildings
			unset($buildings);
			$buildings = array();
			foreach($building_list as $plot) {
				if ($plot['className'] == 'HalloweenHauntedHouseBuilding') {
				list($vUSec,$vSec) = explode(" ", microtime());
				$vPlantTime=(string)$vSec.substr((string)$vUSec, 2, 3);
					if($plot['plantTime']<($vPlantTime-86400000))
						$buildings[] = $plot;
				}
			}
			if (count($buildings) > 0)
				Do_Farm_Work($buildings); //harvest buildings
			unset($buildings);
			$buildings = array();
			foreach($building_list as $plot) {
				if ($plot['className'] == 'FeatureBuilding') {
					if(($enable_harvest_building_wworkshop == 1 && $plot['itemName']=='winterworkshop_finished')
					  ||
					  ($enable_harvest_building_snowman == 1 && $plot['itemName']=='snowman2010_finished')
					  ||
					  ($enable_harvest_building_duckpond == 1 && $plot['itemName']=='duckpond_finished')
					  ||
					  ($enable_harvest_building_ccastle == 1 && $plot['itemName']=='valentines2011_finished')

					  ) {
					list($vUSec,$vSec) = explode(" ", microtime());
					$vPlantTime=(string)$vSec.substr((string)$vUSec, 2, 3);
					if($plot['plantTime']<($vPlantTime-86400000))
						$buildings[] = $plot;
				}
			}
			}
			if (count($buildings) > 0)
				Do_Farm_Work($buildings); //harvest buildings
			unset($building_list);
		}
	}

	Hook('after_harvest_buildings'); //after building harvest

	Hook('before_harvest_animals'); //get product from livestock

	if ($enable_harvest_animal) {
		AddLog2("harvest animals");
		$animals = GetObjects('Animal'); //get list of animals
		$Transforms = unserialize(file_get_contents('transforms.txt')); //get transforms
		$harvest_animals = array();
		$transform_animals = array();

		foreach($animals as $animal) {
			if (($animal['state'] != "grown") && ($animal['state'] != "ripe"))
				continue;

			$px_animal_check = $animal['itemName'];

			if (isset($px_Setopts['H_Animal'])) {
				foreach($px_Setopts['H_Animal'] as $px_a_name => $px_h_check) {
					if ((stristr($px_animal_check, $px_a_name) !== false) && ($px_h_check != 1)) {
						// skip this animal
						continue 2;
					}
				}
			} else {
				AddLog("H_Animal not set.. skipping animal harvest");
				AddLog2("H_Animal not set.. skipping animal harvest");
				break;
			}
			// H_Animal should always be set
			// if we transformed animals on accident ppl would probably be mad lol
			if (in_array($animal['itemName'], $Transforms)) {
				$transform_animals[] = $animal;
			} else {
				$harvest_animals[] = $animal;
			}
		}


		if ($enable_harvest_farmhands) {

		  $vRatio=round((count($transform_animals)+count($harvest_animals))*100/count($animals));

		  if($vRatio>=$enable_harvest_farmhands_at) {

			  $inconbox = @unserialize(file_get_contents(F('inconbox.txt')));
			  if((!isset($inconbox['AA'])) || $inconbox['AA']==0) {
				  AddLog2("farmhands: you dont have farmhands");
			  } elseif($inconbox['AA']<=$enable_harvest_farmhands_min) {
				  AddLog2("farmhands: you dont have enough farmhands (".$inconbox['AA'].")");
			  } else {
				  AddLog2("farmhands: harvest now, ".$vRatio."% ready (".$inconbox['AA']." farmhands remaining)");
				  $need_reload=Do_Farmhands_Arborists('farmhands');
			  }


		  } else {
			  AddLog2("farmhands: now ".$vRatio."% ready, harvest later at ".$enable_harvest_farmhands_at."%");
		  }

		} else {

			if (count($transform_animals) > 0)
				Do_Farm_Work($transform_animals, "transform");
			if (count($harvest_animals) > 0)
				Do_Farm_Work($harvest_animals);
		}

		unset($transform_animals, $harvest_animals);
	}

	Hook('after_harvest_animals');

	Hook('before_harvest_tree');

	// harvest from trees
	if ($enable_harvest_tree) {
		AddLog2("harvest trees");
		$trees = array();
		$plot_list = GetObjects('Tree'); //get list of trees
		foreach($plot_list as $plot) {
			if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe'))
				$trees[] = $plot;
		}

		if ($enable_harvest_arborist) {

		  $vRatio=round(count($trees)*100/count($plot_list));

		  if($vRatio>=$enable_harvest_arborist_at) {
			  $inconbox = @unserialize(file_get_contents(F('inconbox.txt')));
			  if((!isset($inconbox['A9'])) || $inconbox['A9']==0) {
				  AddLog2("arborists: you dont have arborists");
			  } elseif($inconbox['A9']<=$enable_harvest_arborist_min) {
				  AddLog2("farmhands: you dont have enough arborists (".$inconbox['A9'].")");
			  } else {
				  AddLog2("arborists: harvest now, ".$vRatio."% ready (".$inconbox['A9']." arborists remaining)");
				  $need_reload=Do_Farmhands_Arborists('arborists');
			  }

		  } else {
			  AddLog2("arborists: now ".$vRatio."% ready, harvest later at ".$enable_harvest_arborist_at."%");
		  }

		} else {

			if (count($trees) > 0)
				Do_Farm_Work($trees); //harvest trees

		}
		unset($trees,$plot_list);
	}

	Hook('after_harvest_tree');

	Hook('before_hoe');

	if ($enable_hoe) { // we've selected to auto-plow plots
		AddLog2("plowing plots");

		if ($need_reload) {
			$res = DoInit(); //reload farm
			$need_reload = false;
		}

		$plots = array();
		$plot_list = GetObjects('Plot');
		foreach($plot_list as $plot) {
			if (($plot['state'] == 'withered') || ($plot['state'] == 'fallow'))
				$plots[] = $plot;
		}
		unset($plot_list);

		if (count($plots) > 0)
			Do_Farm_Work_Plots($plots, 'plow'); //plow land
		unset($plots);
	}

	Hook('after_hoe');

	Hook('before_before_planting');

	Hook('before_planting');

	if ($need_reload) {
		$res = DoInit(); //reload farm
		$need_reload = false;
	}
	// planting
	if (($enable_seed||$enable_combine) && (file_exists(F('seed.txt')))) { // fix infinite loop when no file exists
		// get list of plants
		$seed_list = explode(';', trim(file_get_contents(F('seed.txt'))));
		// We need to move Default seed to the end of the array
		// and normalize entries

		foreach($seed_list as $one_seed_string) {
			$one_seed_array = @explode(':', $one_seed_string);

			if($one_seed_array[0]=='') {
			  ;
			} elseif($one_seed_array[1]=='Default') {
				$seed_default=$one_seed_array[0];
			} else {
				if($last_seed==$one_seed_array[0]) {
					$last_seed_string=array_pop($seed_list_new);
					$last_seed_array = @explode(':', $last_seed_string);
					$seed_list_new[]=$one_seed_array[0].':'.($one_seed_array[1]+$last_seed_array[1]);
				} else {
					$seed_list_new[]=$one_seed_string;
				}
				$last_seed=$one_seed_array[0];
			}
		}
		if(isset($seed_default)) $seed_list_new[]=$seed_default.':Default';
		$seed_list=$seed_list_new;
		unset($seed_list_new);

		$plots = GetObjects('Plot');
		// Find empty plots
		$plowed_plots = array();
		foreach($plots as $plot) {
			if ($plot['state'] == 'plowed')
				$plowed_plots[] = $plot;
			if ($enable_combine && (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') || ($plot['state'] == 'withered') || ($plot['state'] == 'fallow')))
				$plowed_plots[] = $plot;

		}

		$seed_plots = array();
		$append_seed_array = array();
		foreach($plowed_plots as $plowed_key => $plot) {
			foreach($seed_list as $seed_key => $itemName) {
				$px_itemName = explode(':', $itemName);

				if (empty($px_itemName[0]))
					break 2; //seedlist is empty
				$plot['itemName'] = $px_itemName[0];
				$seed_plots[] = $plot;

				if ($px_itemName[1] != "Default") {
					$px_itemName[1]--;
					if ($px_itemName[1] == 0) {
						unset($seed_list[$seed_key]);
					} else {
						$seed_list[$seed_key] = "$px_itemName[0]:$px_itemName[1]";
					}
					if($keep_seed)
						$append_seed_array[$px_itemName[0]]++;
				}
				break;
			} //seedlist
		} //plotlist
		// save list
		if($keep_seed) {
			foreach($append_seed_array as $append_seed=>$append_count) {
				$seed_list[]=$append_seed.':'.$append_count;
			}
		}
		file_put_contents(F('seed.txt'),implode(';', $seed_list));

		if (count($seed_plots) > 0) {
			if($enable_combine) Do_Farm_Work_Plots($seed_plots, 'combine'); //combine
			if($enable_seed) Do_Farm_Work_Plots($seed_plots, 'place'); //plant crops
		}
		unset($seed_list, $seed_data, $seed_plots, $plowed_plots);
	}

	Hook('after_planting');

	Hook('before_missions');
	# missions removed due to z* changes

	Hook('after_missions');

	// if ($need_reload)
	$res_str = "reload\r\n" . $res_str;

	EchoData($res_str);

	Hook('after_work');

	Parser_Check_Images();

	AddLog2("memory_peak_usage: ".round(memory_get_peak_usage(true)/1024/1024,2)."MB");
	AddLog2("finish");
}

// ------------------------------------------------------------------------------
// Parser_Check_Images
// ------------------------------------------------------------------------------
function Parser_Check_Images() {
	global $vDataDB,$flashRevision;
	AddLog2("parser: check images");

	if(!file_exists('swfdump.exe')) {
		AddLog2("parser: swfdump.exe missing");
		return'';
	}

	$vHaveFlash=false;

	$vDataDB->queryExec('BEGIN TRANSACTION');
	$vSQL='select * from units where field="iconurl" and name not in (select name from units where field="imageready")';
	$vResult = @$vDataDB->query($vSQL);

	while (@$vRow = $vResult->fetch(SQLITE_ASSOC)) {

		$vImage=$vRow['content'];
		$vName=$vRow['name'];

		if (!file_exists($vImage)) {
			if(!$vHaveFlash) {

			  $vWorld=unserialize(file_get_contents(F('world.txt'))); //get the last world
			  $vFlashVersion=$vWorld['data'][0]['data']['userInfo']['player']['_explicitType'];
			  if(strlen($vFlashVersion)==0)
				  $vFlashVersion=$vWorld['data'][0]['data']['userInfo']['player']['energyManager']['_explicitType'];
			  if(strlen($vFlashVersion)==0)
				  $vFlashVersion=$vWorld['data'][0]['data']['userInfo']['player']['licenseManager']['_explicitType'];
			  unset($vWorld);

			  $vDir='./farmville-flash';
			  if (!is_dir($vDir)) {
				  @mkdir($vDir);
			  }
			  $time_limit = 7*24*60*60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
			  if ($df = opendir($vDir)) {
				  while (false !== ($file = readdir($df))) {
					  if ($file != "." && $file != "..") {
						  $file1=$vDir.'/'.$file;
						  $last_modified = filemtime($file1);
						  if(time()-$last_modified > $time_limit){
							  unlink($file1);
						  }
					  }
				  }
				  closedir($df);
			  }

			  $vFlashRelease = explode('.', $vFlashVersion);
			  $vFlashFile='FarmGame.'.$vFlashRelease[3].'.'.$flashRevision.'.swf';
			  $vFlashURL = 'http://static.farmville.com/embeds/v'.$flashRevision.'/'.$vFlashFile;
			  if (!file_exists('farmville-flash/'.$vFlashFile)) {

				  $vFlashContent = file_get_contents($vFlashURL);
				  if($vFlashContent === false) {
					  AddLog2('Unable to get Flash File');
					  return;
				  } else {
					  file_put_contents('farmville-flash/'.$vFlashFile, $vFlashContent);
				  }
			  }
			  if (!file_exists('farmville-flash/'.$vFlashFile.'.txt.')) {
				  $vFlashDump=shell_exec('swfdump.exe -a farmville-flash/'.$vFlashFile.' 2>&1 ');
				  #$vFlashDump=substr($vFlashDump,strpos($vFlashDump, "StorageConfigSettings=StorageConfigSettings"));
				  $vFlashDump=substr($vFlashDump,strpos($vFlashDump, "slot 0: class <q>[public]Classes.Yimf::YimfMap=YimfMap"));
				  $vLastPos=strpos($vFlashDump,'Display::AssetHashMap');
				  if($vLastPos>1000) {
					  $vFlashDump=substr($vFlashDump,0,$vLastPos);
				  }
				  file_put_contents('farmville-flash/'.$vFlashFile.'.txt.', $vFlashDump);
			  } else {
				  $vFlashDump = file_get_contents('farmville-flash/'.$vFlashFile.'.txt.');
			  }
			  $vHaveFlash=true;
			}

			$vHashImage='';
			$vUrlPosition = strpos($vFlashDump, "pushstring \"".$vImage);
			if($vUrlPosition !== false) {
				$vUrlPosition += 12;
				$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
				$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
				if($vUrlPosition2 !== false) {
					$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $vUrlLength);
				}
			}

			if(strlen($vHashImage)>0) {

				$vFolder = substr($vImage, 0, strrpos($vImage, "/"));
				if (!is_dir($vFolder)) {
					@mkdir($vFolder, 0777, true);
				}
				#http://static-0.farmville.com/prod/hashed/assets/animals/c8cebb78febd1ab62ffb9db67d283e89.png
				$vRemoteUrl = 'http://static.farmville.com/prod/hashed/'.$vHashImage;

				$vImageData = file_get_contents($vRemoteUrl);
				if($vImageData) {
					file_put_contents($vImage, $vImageData);
					@$vDataDB->queryExec('insert into units("name","field","content") values("'.$vName.'","imageready","download")');
					AddLog2("parser: get images ".$vImage);
					error_log('"'.$vImage.'";"'.$vHashImage.'";"'.date('Y.m.d H:i:s').'"'."\n",3,LogF('image_download_log.csv'));
				}

			}

		} else {
			@$vDataDB->queryExec('insert into units("name","field","content") values("'.$vName.'","imageready","found")');
		}

	}
	$vDataDB->queryExec('COMMIT TRANSACTION');
	AddLog2("parser: check images done");

}
// ------------------------------------------------------------------------------
// Parser_SQlite_Connect
// ------------------------------------------------------------------------------
function Parser_SQlite_Connect($vDBFile) {
	$vDB = new SQLiteDatabase($vDBFile);
	if (!$vDB) {
		AddLog2('Parser SQlite Error: cant open '.$vDBFile);
		return(false);
	}
	$vDB->queryExec('PRAGMA cache_size=200000');
	$vDB->queryExec('PRAGMA synchronous=OFF');
	$vDB->queryExec('PRAGMA count_changes=OFF');
	$vDB->queryExec('PRAGMA journal_mode=MEMORY');
	$vDB->queryExec('PRAGMA temp_store=MEMORY');
	return $vDB;
}

// ------------------------------------------------------------------------------
// Units_GetUnitByName get unit by Name
// ------------------------------------------------------------------------------
function Units_GetUnitByName($vName, $vAllInfo=false) {
	global $vDataDB;
	if($vAllInfo) {
		$vSQL='select * from units where name="'.$vName.'"';
	} else {
		$vSQL='select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc") and name="'.$vName.'"';
	}
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		#$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
		$vReturn[$vRow['field']]=$vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetUnitByCode get unit by Name
// ------------------------------------------------------------------------------
function Units_GetUnitByCode($vCode, $vAllInfo=false) {
	return(Units_GetUnitByName(Units_GetNameByCode($vCode), $vAllInfo));
}

// ------------------------------------------------------------------------------
// Units_GetRealnameByName
// ------------------------------------------------------------------------------
function Units_GetRealnameByName($vName) {
	global $vDataDB;
	$vSQL='select content from units where name="'.$vName.'" and field="realname"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn=$vResult->fetchSingle();
	return($vReturn==''?$vName:$vReturn);
}


// ------------------------------------------------------------------------------
// Units_GetNameByRealname
// ------------------------------------------------------------------------------
function Units_GetNameByRealname($vRealName) {
	global $vDataDB;
	$vSQL='select name from units where content="'.$vName.'" and field="realname"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn=$vResult->fetchSingle();
	return($vReturn==''?$vName:$vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetCodeByName
// ------------------------------------------------------------------------------
function Units_GetCodeByName($vName) {
	global $vDataDB;
	$vSQL='select content from units where name="'.$vName.'" and field="code"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn=$vResult->fetchSingle();
	return($vReturn==''?$vName:$vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetNameByCode
// ------------------------------------------------------------------------------
function Units_GetNameByCode($vCode) {
	global $vDataDB;
	$vSQL='select name from units where content="'.$vCode.'" and field="code"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn=$vResult->fetchSingle();
	return($vReturn==''?$vCode:$vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetRealnameByCode
// ------------------------------------------------------------------------------
function Units_GetRealnameByCode($vCode) {
	global $vDataDB;
	$vSQL='select content from units where field="realname" and name in (select name from units where content="'.$vCode.'" and field="code")';
	$vResult = $vDataDB->query($vSQL);
	$vReturn=$vResult->fetchSingle();
	return($vReturn==''?$vCode:$vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetAll get all units
// ------------------------------------------------------------------------------
function Units_GetAll($vAllInfo=false) {
	global $vDataDB;
	if($vAllInfo) {
		$vSQL='select * from units';
	} else {
		$vSQL='select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc")';
	}
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetByType get all units of type $vType
// ------------------------------------------------------------------------------
function Units_GetByType($vType, $vAllInfo=false) {
	global $vDataDB;
	if($vAllInfo) {
		$vSQL='select * from units where name in (select name from units where field="type" and content="'.$vType.'")';
	} else {
		$vSQL='select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc") and name in (select name from units where field="type" and content="'.$vType.'")';
	}
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Units_GetByClass get all units of type $vType
// ------------------------------------------------------------------------------
function Units_GetByClass($vClass, $vAllInfo=false) {
	global $vDataDB;
	if($vAllInfo) {
		$vSQL='select * from units where name in (select name from units where field="className" and content="'.$vClass.'")';
	} else {
		$vSQL='select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc") and name in (select name from units where field="class" and content="'.$vClass.'")';
	}
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------n
// Units_GetCodeByName
// ------------------------------------------------------------------------------
function Units_GetFarming($vField) {
	global $vDataDB;
	$vSQL='select content from units where name="_farming" and field="'.$vField.'"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn=$vResult->fetchSingle();
	return($vReturn==''?$vField:$vReturn);
}


// ------------------------------------------------------------------------------
// Units_IsConsumableByName get unit by Name
// ------------------------------------------------------------------------------
function Units_IsConsumableByName($vName) {
	global $vDataDB;
	$vSQL='select count(*) from units where name="'.$vName.'" and content="consumable" and field in ("type","subtype")';
	$vResult = @$vDataDB->query($vSQL);
	return(@$vResult->fetchSingle()==0?false:true);
}


// ------------------------------------------------------------------------------
// Quests_GetQuestByName get unit by Name
// ------------------------------------------------------------------------------
function Quests_GetQuestByName($vName) {
	global $vDataDB;
	$vSQL='select * from quests where name="'.$vName.'"';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		#$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
		$vReturn[$vRow['field']]=$vRow['content'];
	}
	return($vReturn);
}

// ------------------------------------------------------------------------------
// Quests_GetAll get all quests
// ------------------------------------------------------------------------------
function Quests_GetAll() {
	global $vDataDB;
	$vSQL='select * from quests';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
	}
	return($vReturn);
}


// ------------------------------------------------------------------------------
// parse_flashvars
// ------------------------------------------------------------------------------
function parse_flashvars() {
	$temp = file_get_contents(F('flashVars.txt'));
	preg_match('/var flashVars = (\{[^}]*\})/sim', $temp, $flash);
	return json_decode($flash[1],true);
}

// ------------------------------------------------------------------------------
// GetUnitList
// ------------------------------------------------------------------------------
function GetUnitList() {
	global $userId, $flashRevision, $botlitever;
	global $vDataDB;

	if(isset($botlitever)) {
		$argv = @$GLOBALS['argv'];
		$userId = @$argv[2];
		$flashRevision = @$argv[3];
		LoadAuthParams();
	} else {
		$datasize = hexdec(fread(STDIN, 8));
		$data = fread(STDIN, $datasize);
		@list($userId, $flashRevision, $sequence, $flashSessionKey, $xp, $energy) = @explode(';', $data);
	}

	define ('farmer', GetFarmserver());
	define ('farmer_url', GetFarmUrl());

	if (@$flashRevision == '' || @$flashRevision == 'null' || @$flashRevision == 'reload' || @$flashRevision == 'OK') {
		echo "Flash Revision unknown. A plugin likely has an error\r\n";
		AddLog2("Flash Revision unknown.\r\n A plugin likely has an error");
		RestartBot();
		return;
	}

	$vDir='./farmville-xml';
	if (!is_dir($vDir)) @mkdir($vDir);
	$time_limit = 7*24*60*60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir)) {
		while (false !== ($file = readdir($df))) {
			if ($file != "." && $file != "..") {
				$file1=$vDir.'/'.$file;
				$last_modified = filemtime($file1);
				if(time()-$last_modified > $time_limit) unlink($file1);
			}
		}
		closedir($df);
	}
	$vDir='./farmville-sqlite';
	if (!is_dir($vDir)) {
		@mkdir($vDir);
	}
	$time_limit = 7*24*60*60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir)) {
		while (false !== ($file = readdir($df))) {
			if ($file != "." && $file != "..") {
				$file1=$vDir.'/'.$file;
				$last_modified = filemtime($file1);
				if(time()-$last_modified > $time_limit) unlink($file1);
			}
		}
		closedir($df);
	}

	$vDir='./farmville-logs';
	if (!is_dir($vDir)) @mkdir($vDir);
	$time_limit = 7*24*60*60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir)) {
		while (false !== ($file = readdir($df))) {
			if ($file != "." && $file != "..") {
				$file1=$vDir.'/'.$file;
				$last_modified = filemtime($file1);
				if(time()-$last_modified > $time_limit) unlink($file1);
			}
		}
		closedir($df);
	}

	$sqlite_update = 0; //if 1 we are going to download new xml from server

	if (file_exists('sqlite_check.txt')) {
		@$sqlite_flashRevision = file_get_contents('sqlite_check.txt');
		if ($sqlite_flashRevision <> $flashRevision) {
			$sqlite_update = 1;
		}
	} else $sqlite_update = 1;

	if ($sqlite_update == 1) {
	  $vDataDB=null;
	  @copy(PARSER_SQLITE,'./farmville-sqlite/'.date('Ymd_His').'.sqlite');
	  @unlink(PARSER_SQLITE);
	  $vDataDB=Parser_SQlite_Connect(PARSER_SQLITE);
	}

	# check units table
	if (@$vDataDB->query('SELECT * FROM units limit 1') === false) {
	  $vSQL='CREATE TABLE
			  units (
				name CHAR(25),
				field CHAR(25),
				content CHAR(250)
			  )';
	  $vDataDB->queryExec($vSQL);
	  $vDataDB->queryExec('CREATE INDEX units_idx_1 ON units(name,field)');
	  $vDataDB->queryExec('CREATE INDEX units_idx_2 ON units(field,content)');
	  $sqlite_update = 1;
	}

	# check achievements table
	if (@$vDataDB->query('SELECT * FROM achievements limit 1') === false) {
	  $vSQL='CREATE TABLE
			  achievements (
				name CHAR(25),
				field CHAR(25),
				content CHAR(250)
			  )';
	  $vDataDB->queryExec($vSQL);
	  $vDataDB->queryExec('CREATE INDEX achievements_idx_1 ON achievements(name,field)');
	  $vDataDB->queryExec('CREATE INDEX achievements_idx_2 ON achievements(field,content)');
	  $sqlite_update = 1;
	}

	# check collectables table
	if (@$vDataDB->query('SELECT * FROM collectables limit 1') === false) {
	  $vSQL='CREATE TABLE
			  collectables (
				name CHAR(25),
				field CHAR(25),
				content CHAR(250)
			  )';
	  $vDataDB->queryExec($vSQL);
	  $vDataDB->queryExec('CREATE INDEX collectables_idx_1 ON collectables(name,field)');
	  $vDataDB->queryExec('CREATE INDEX collectables_idx_2 ON collectables(field,content)');
	  $sqlite_update = 1;
	}
	# check storage table
	if (@$vDataDB->query('SELECT * FROM storage limit 1') === false) {
	  $vSQL='CREATE TABLE
			  storage (
				name CHAR(25),
				field CHAR(25),
				content CHAR(250)
			  )';
	  $vDataDB->queryExec($vSQL);
	  $vDataDB->queryExec('CREATE INDEX storage_idx_1 ON storage(name,field)');
	  $vDataDB->queryExec('CREATE INDEX storage_idx_2 ON storage(field,content)');
	  $sqlite_update = 1;
	}
	# check crafting table
	if (@$vDataDB->query('SELECT * FROM crafting limit 1') === false) {
	  $vSQL='CREATE TABLE
			  crafting (
				name CHAR(25),
				field CHAR(25),
				content CHAR(250)
			  )';
	  $vDataDB->queryExec($vSQL);
	  $vDataDB->queryExec('CREATE INDEX crafting_idx_1 ON crafting(name,field)');
	  $vDataDB->queryExec('CREATE INDEX crafting_idx_2 ON crafting(field,content)');
	  $sqlite_update = 1;
	}
	# check quests table
	if (@$vDataDB->query('SELECT * FROM quests limit 1') === false) {
	  $vSQL='CREATE TABLE
			  quests (
				name CHAR(25),
				field CHAR(50),
				content CHAR(250)
			  )';
	  $vDataDB->queryExec($vSQL);
	  $vDataDB->queryExec('CREATE INDEX quests_idx_1 ON quests(name,field)');
	  $vDataDB->queryExec('CREATE INDEX quests_idx_2 ON quests(field,content)');
	  $sqlite_update = 1;
	}

	// Force download when key files are missing
	if (!file_exists('units.txt')) {
		$sqlite_update = 1;
	}
	if (!file_exists('collectable_info.txt')) {
		$sqlite_update = 1;
	}
	if (!file_exists('achievement_info.txt')) {
		$sqlite_update = 1;
	}

	AddLog2("Downloading latest game files.");
	$flashVars = parse_flashvars();
	$vNotFound=0;

	$vFlashLocale='./farmville-xml/'.$flashRevision.'_flashLocaleXml.xml';
	if (!file_exists($vFlashLocale)) {
		// load description
		$xml_locales = '';
		if (file_exists('desc_url.txt')) {
			$geturl = trim(file_get_contents('desc_url.txt'));
			$xml_locales = file_get_contents($geturl);
			if (!$xml_locales) { // Null if the file doesn't exist / server returned 404 error
				AddLog2("File: desc_url.txt contains invalid url, skipping..");
			} else {
				AddLog2("Loaded settings from desc_url.txt.");
			}
		}
		if (!$xml_locales) {
			AddLog2("DL: v$flashRevision descriptions xml");
			//$geturl = "http://" . farmer . "/v$flashRevision/flashLocaleXml.xml";
			$geturl = "http://static.farmville.com/xml/gz/v$flashRevision/flashLocaleXml.xml";
			$xml_locales = proxy_GET($geturl);
		}
		if (!$xml_locales) {
			AddLog2("Couldn't find descriptions xml.");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vFlashLocale,$xml_locales);
			$sqlite_update = 1;
		}
		unset($xml_locales);
	}

	$vGameSetting='./farmville-xml/'.$flashRevision.'_gameSettings.xml';
	if (!file_exists($vGameSetting)) {
		$xml_units = '';
		if (file_exists('override_url.txt')) {
			$geturl = trim(file_get_contents('override_url.txt'));
			$xml_units = file_get_contents($geturl);
			if (!$xml_units) { // Null if the file doesn't exist / server returned 404 error
				AddLog2("File: override_url.txt contains invalid url, skipping..");
			} else {
				AddLog2("Loaded settings from override_url.txt.");
			}
		}
		if (!$xml_units) {
			AddLog2("DL: v$flashRevision settings file.");
			//$geturl = "http://static-facebook.farmville.com/v$flashRevision/gameSettings.xml.gz";
			$geturl = $flashVars['game_config_url'];
			$xml_units = proxy_GET($geturl);
		}
		if (!$xml_units) {
			AddLog2("Couldn't find a settings xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vGameSetting,$xml_units);
			$sqlite_update = 1;
		}
		unset($xml_units);
	}

	$vItemsSetting='./farmville-xml/'.$flashRevision.'_items.xml';
	if (!file_exists($vItemsSetting)) {
		$xml_items = '';
		if (file_exists('override_items_url.txt')) {
			$geturl = trim(file_get_contents('override_items_url.txt'));
			$xml_items = file_get_contents($geturl);
			if (!$xml_items) { // Null if the file doesn't exist / server returned 404 error
				AddLog2("File: override_items_url.txt contains invalid url, skipping..");
			} else {
				AddLog2("Loaded settings from override_items_url.txt.");
			}
		}
		if (!$xml_items) {
			AddLog2("DL: v$flashRevision items xml.");
			//$geturl = "http://static-facebook.farmville.com/v$flashRevision/items.xml.gz";
			$geturl = $flashVars['items_url'];
			$xml_items = proxy_GET($geturl);
		}
		if (!$xml_items) {
			AddLog2("Couldn't find a items xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vItemsSetting,$xml_items);
			$sqlite_update = 1;
		}
		unset($xml_items);
	}

	$vStorageConfig='./farmville-xml/'.$flashRevision.'_StorageConfig.xml';
	if (!file_exists($vStorageConfig)) {
		$xml_storage='';
		AddLog2("DL: v$flashRevision storageconfig xml");
		$geturl = "http://fb-tc-1.farmville.com/v$flashRevision/StorageConfig.xml.gz";
		$xml_storage = proxy_GET($geturl);
		if (!$xml_storage) {
			AddLog2("Storageconfig xml attempt #2");
			$geturl = "http://fb-ak-1.farmville.com/StorageConfig.xml";
			$xml_storage = proxy_GET($geturl);
		}
		if (!$xml_storage) {
			AddLog2("Couldn't find a storageconfig xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vStorageConfig,$xml_storage);
			$sqlite_update = 1;
		}
		unset($xml_storage);
	}

	$vQuestsConfig='./farmville-xml/'.$flashRevision.'_Quests.xml';
	if (!file_exists($vQuestsConfig)) {
		$xml_quests='';
		AddLog2("DL: v$flashRevision quests xml");
		$geturl = "http://static.farmville.com/xml/gz/v$flashRevision/quests.xml.gz";
		$xml_quests = proxy_GET($geturl);
		if (!$xml_quests) {
			AddLog2("Couldn't find a quests xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vQuestsConfig,$xml_quests);
			$sqlite_update = 1;
		}
		unset($xml_quests);
	}

	$vCraftingConfig='./farmville-xml/'.$flashRevision.'_Crafting.xml';
	if (!file_exists($vCraftingConfig)) {
		$xml_crafting='';
		AddLog2("DL: v$flashRevision crafting xml");
		$geturl = "http://static.farmville.com/xml/gz/v$flashRevision/crafting.xml.gz";
		$xml_crafting = proxy_GET($geturl);
		if (!$xml_crafting) {
			AddLog2("Couldn't find a crafting xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vCraftingConfig,$xml_crafting);
			$sqlite_update = 1;
		}
		unset($xml_crafting);
	}

	$vMarketDataConfig='./farmville-xml/'.$flashRevision.'_MarketData.xml';
	if (!file_exists($vMarketDataConfig)) {
		$xml_marketdata='';
		AddLog2("DL: v$flashRevision marketdata xml");
		$geturl = "http://static.farmville.com/xml/gz/v$flashRevision/MarketData.xml.gz";
		$xml_marketdata = proxy_GET($geturl);
		if (!$xml_marketdata) {
			AddLog2("Couldn't find a marketdata xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vMarketDataConfig,$xml_marketdata);
			$sqlite_update = 1;
		}
		unset($xml_marketdata);
	}

	$vDialogsConfig='./farmville-xml/'.$flashRevision.'_Dialogs.xml';
	if (!file_exists($vDialogsConfig)) {
		$xml_dialogs='';
		AddLog2("DL: v$flashRevision dialogs xml");
		$geturl = "http://static.farmville.com/xml/gz/v$flashRevision/dialogs.xml.gz";
		$xml_dialogs = proxy_GET($geturl);
		if (!$xml_dialogs) {
			AddLog2("Couldn't find a dialogs xml...");
			$vNotFound++;
		} else {
			AddLog2("Download completed.");
			file_put_contents($vDialogsConfig,$xml_dialogs);
			$sqlite_update = 1;
		}
		unset($xml_dialogs);
	}

	$vAvatarConfig='./farmville-xml/'.$flashRevision.'_avatar.xml';
	if (!file_exists($vAvatarConfig)) {
		$xml_avatar='';
		AddLog2("DL: v$flashRevision avatar xml");
		$geturl = "http://fb-tc-1.farmville.com/xml/gz/v$flashRevision/avatar.xml.gz";
		$xml_avatar = proxy_GET($geturl);
		if (!$xml_avatar) {
			AddLog2("AvatarConfig xml attempt #2");
			$geturl = "http://fb-tc-0.farmville.com/avatar.xml";
			$xml_avatar = proxy_GET($geturl);
		}
		if (!$xml_avatar) {
			AddLog2("Couldn't find a avatar xml...");
		} else {
			AddLog2("Download completed.");
			file_put_contents($vAvatarConfig,$xml_avatar);
			$sqlite_update = 1;
		}
		unset($xml_avatar);
	}

	if ($sqlite_update == 1 && $vNotFound > 0) {
		AddLog2("Error Downloading latest game files! Running with old data!!");
		$sqlite_update = 0;
		unlink('sqlite_check.txt');
		sleep(10);
		return;
	}
	if ($sqlite_update == 1) {
		$vDataDB->queryExec('BEGIN TRANSACTION');
		@$vDataDB->queryExec('delete from units');
		@$vDataDB->queryExec('delete from achievements');
		@$vDataDB->queryExec('delete from collectables');
		@$vDataDB->queryExec('delete from storage');
		@$vDataDB->queryExec('delete from crafting');
		@$vDataDB->queryExec('delete from quests');
		@$vDataDB->queryExec('vacuum');
		$vDataDB->queryExec('COMMIT TRANSACTION');

		$vDataDB->queryExec('BEGIN TRANSACTION');

		$xmlDoc = simplexml_load_file($vItemsSetting);
		foreach ($xmlDoc->items->item as $vItem) {
			$vItemName=(string)$vItem['name'];

			if(strlen($vItemName)>0) {
				$vCntMastery=0;
				$vCntRequirements=0;
				foreach($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if($vSubName=='image') {
						if($vSubElement['name']=='icon') {
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","iconurl","'.$vSubElement['url'].'");');
						}
					} elseif($vSubName=='requirements') {
						foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
							if($vSubSubName=='requirement') {
								$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","requirement_'.$vCntRequirements.'_number","'.($vCntRequirements+1).'");');
								$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","requirement_'.$vCntRequirements.'_className","'.$vSubSubElement['className'].'");');
								$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","requirement_'.$vCntRequirements.'_name","'.$vSubSubElement['name'].'");');
								$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","requirement_'.$vCntRequirements.'_level","'.$vSubSubElement['level'].'");');
								$vCntRequirements++;
							}
						}
					} elseif($vSubName=='masteryLevel') {
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","masteryLevel_'.$vCntMastery.'_level","'.($vCntMastery+1).'");');
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","masteryLevel_'.$vCntMastery.'_count","'.$vSubElement['count'].'");');
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","masteryLevel_'.$vCntMastery.'_xp","'.$vSubElement['xp'].'");');
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","masteryLevel_'.$vCntMastery.'_coins","'.$vSubElement['coins'].'");');
						if($vSubElement['gift']) {
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","masteryLevel_'.$vCntMastery.'_gift","'.$vSubElement['gift'].'");');
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","masterymax","'.$vSubElement['count'].'");');
						}
						$vCntMastery++;
					} elseif($vSubName<>'sounds') {
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.(string)$vSubElement.'");');
					}
				}
			}
		}

		$xmlDoc = simplexml_load_file($vGameSetting);
		foreach($xmlDoc->farming as $vItem) {
			foreach($vItem->attributes() as $vField => $vContent) {
				$vDataDB->queryExec('insert into units(name,field,content) values("_farming","'.$vField.'","'.$vContent.'");');
			}
		}
		foreach ($xmlDoc->collections->collection as $vItem) {
			$vItemName=(string)$vItem['name'];

			if(strlen($vItemName)>0) {
				foreach($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if($vSubName=='collectable') {

						$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vItemName.'","collectable","'.$vSubElement['code'].'");');
						if(isset($vSubElement['chance'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vSubElement['code'].'","chance","'.$vSubElement['chance'].'");');
						}
						if(isset($vSubElement['rarity'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vSubElement['code'].'","rarity","'.$vSubElement['rarity'].'");');
						}
						if(isset($vSubElement['source'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vSubElement['code'].'","source","'.$vSubElement['source'].'");');
						}
						if(isset($vSubElement['numneeded'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vSubElement['code'].'","numneeded","'.$vSubElement['numneeded'].'");');
						}
					}
					if($vSubName=='tradeInReward') {
						if(isset($vSubElement['xp'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vItemName.'","tradeInReward_xp","'.$vSubElement['xp'].'");');
						}
						if(isset($vSubElement['coins'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vItemName.'","tradeInReward_coins","'.$vSubElement['coins'].'");');
						}
						if(isset($vSubElement['gift'])) {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vItemName.'","tradeInReward_gift","'.$vSubElement['gift'].'");');
						}
					}
				}
			}
		}
		foreach ($xmlDoc->achievements->achievement as $vItem) {
			$vItemName=(string)$vItem['name'];
			$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","type","achieve");');

			if(strlen($vItemName)>0) {
				foreach($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
					$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if($vSubName=='image') {
						if($vSubElement['name']=='icon_48') {
							$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vItemName.'","iconurl","'.$vSubElement['url'].'");');
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vItemName.'","iconurl","'.$vSubElement['url'].'");');
						}
					} elseif($vSubName='level') {
						$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vItemName.'","count","'.$vSubElement['count'].'");');
						$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vItemName.'","xp","'.$vSubElement['xp'].'");');
						$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vItemName.'","coins","'.$vSubElement['coins'].'");');
					}
				}
			}
		}
		unset($xmlDoc);

		$xmlDoc = simplexml_load_file($vFlashLocale);
		foreach ($xmlDoc->bundle as $vItem) {
			$vType=(string)$vItem['name'];
			if($vType=='Items' || $vType=='Collections' || $vType=='Achievements') {
				foreach ($vItem->children() as $vSubElement) {
					$vName=(string)$vSubElement['key'];
					if(substr($vName,-12)=='friendlyName') {
						$vName=substr($vName,0,-13);
						$vRealName=(string)$vSubElement->value;
						if($vType=='Items') {
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vName.'","realname","'.$vRealName.'");');
						}
						if($vType=='Collections') {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vName.'","realname","'.$vRealName.'");');
						}
						if($vType=='Achievements') {
							$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vName.'","realname","'.$vRealName.'");');
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vName.'","realname","'.$vRealName.'");');
						}
					}
					if(substr($vName,-11)=='description') {
						$vName=substr($vName,0,-12);
						$vDescription=(string)$vSubElement->value;
						if($vType=='Items') {
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vName.'","desc","'.$vDescription.'");');
						}
						if($vType=='Collections') {
							$vDataDB->queryExec('insert into collectables(name,field,content) values("'.$vName.'","desc","'.$vDescription.'");');
						}
						if($vType=='Achievements') {
							$vDataDB->queryExec('insert into achievements(name,field,content) values("'.$vName.'","desc","'.$vDescription.'");');
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vName.'","desc","'.$vDescription.'");');
						}
					}
				}
			}
		}
		unset($xmlDoc);

		$xmlDoc = simplexml_load_file($vStorageConfig);
		foreach ($xmlDoc->StorageEntity as $vItem) {
			$vItemName=(string)$vItem['name'];
			if(strlen($vItemName)>0) {
				$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","type","StorageEntity");');
				foreach($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if($vSubName=='allowedClass') {
						$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.$vSubElement['type'].'");');
					} elseif($vSubName=='nonStorableClass' || $vSubName=='denyKeyword' || $vSubName=='allowKeyword') {
						$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.(string)$vSubElement.'");');
					}
				}
			}
		}
		foreach ($xmlDoc->StorageBuilding as $vItem) {
			$vItemName=(string)$vItem['name'];
			if(strlen($vItemName)>0) {
				$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","type","StorageBuilding");');
				foreach($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if($vSubName=='allowedClass') {
						$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.$vSubElement['type'].'");');
					} elseif($vSubName=='nonStorableClass' || $vSubName=='denyKeyword' || $vSubName=='allowKeyword') {
						$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.(string)$vSubElement.'");');
					} elseif($vSubName=='itemName') {
						if($vSubElement['part']=='true') {
							$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","part","'.(string)$vSubElement.'");');
							$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.(string)$vSubElement.'_need","'.$vSubElement['need'].'");');
						} else {
							$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.(string)$vSubElement.'");');
							if($vSubElement['limit']) {
								$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.(string)$vSubElement.'_limit","'.$vSubElement['limit'].'");');
							}
						}
					}
				}
			}
		}
		foreach ($xmlDoc->FeatureCreditStorage as $vItem) {
			$vItemName=(string)$vItem['name'];
			if(strlen($vItemName)>0) {
				$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","type","FeatureCreditStorage");');
				foreach($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					$vDataDB->queryExec('insert into storage(name,field,content) values("'.$vItemName.'","'.$vSubName.'","'.(string)$vSubElement.'");');
				}
			}
		}
		unset($xmlDoc);

		$xmlDoc = simplexml_load_file($vCraftingConfig);
		foreach ($xmlDoc->recipes->CraftingRecipe as $vRecipe) {
			$vRecipeID=(string)$vRecipe['id'];
			if(strlen($vRecipeID)>0) {
				$vRecipeName='';
				foreach($vRecipe->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into crafting(name,field,content) values("'.$vRecipeID.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vRecipe->children() as $vSubName => $vSubElement) {
					if($vSubName=='name') $vRecipeName=(string)$vSubElement;
					if($vSubName=='image') {
						if($vSubElement['name']=='icon') {
							$vDataDB->queryExec('insert into crafting(name,field,content) values("'.$vRecipeID.'","iconurl","'.$vSubElement['url'].'");');
							$vDataDB->queryExec('insert into units(name,field,content) values("'.$vRecipeID.'","iconurl","'.$vSubElement['url'].'");');
						}
					} elseif($vSubName=='Reward') {
						foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
							foreach($vSubSubElement->attributes() as $vField => $vContent) {
								$vDataDB->queryExec('insert into crafting(name,field,content) values("'.$vRecipeID.'","reward_'.$vSubSubName.'_'.$vField.'","'.(string)$vContent.'");');
							}
						}
					} elseif($vSubName=='Ingredients') {
						foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
							$vDataDB->queryExec('insert into crafting(name,field,content) values("'.$vRecipeID.'","Ingredient_itemCode","'.$vSubSubElement['itemCode'].'");');
							$vDataDB->queryExec('insert into crafting(name,field,content) values("'.$vRecipeID.'","Ingredient_quantityRequired_'.$vSubSubElement['itemCode'].'","'.$vSubSubElement['quantityRequired'].'");');
						}
					} else {
						$vDataDB->queryExec('insert into crafting(name,field,content) values("'.$vRecipeID.'","'.$vSubName.'","'.(string)$vSubElement.'");');
					}
				}
				if(strlen($vRecipeName)>0) {
					$vDataDB->queryExec('update crafting set name="'.$vRecipeName.'" where name="'.$vRecipeID.'"');
				}
			}
		}
		unset($xmlDoc);

		$xmlDoc = simplexml_load_file($vQuestsConfig);
		foreach ($xmlDoc->quest as $vQuest) {
			$vQuestID=(string)$vQuest['id'];
			if(strlen($vQuestID)>0) {
				foreach($vQuest->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","'.$vField.'","'.$vContent.'");');
				}
				foreach ($vQuest->children() as $vSubName => $vSubElement) {
					if($vSubName=='text') {
						foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
							foreach($vSubSubElement->attributes() as $vField => $vContent) {
								$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","'.$vField.'","'.(string)$vContent.'");');
							}
						}
					} elseif($vSubName=='icon') {
						$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","iconurl","'.$vSubElement['url'].'");');
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vQuestID.'","iconurl","'.$vSubElement['url'].'");');
					} elseif($vSubName=='questGiverImage') {
						$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","questGiverImage","'.$vSubElement['url'].'");');
						$vDataDB->queryExec('insert into units(name,field,content) values("'.$vQuestID.'_Giver","iconurl","'.$vSubElement['url'].'");');
					} elseif($vSubName=='completionRequirements') {
						$vCompleteName=$vSubElement['name'];
						$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'","'.$vCompleteName.'");');
						$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'_timeLimit","'.$vSubElement['timeLimit'].'");');

						foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
							if($vSubSubName=='requirement') {
								$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'_'.(string)$vSubSubElement['action'].'_'.(string)$vSubSubElement['type'].'","'.(string)$vSubSubElement['many'].'");');
							}
							if($vSubSubName=='reward') {
								if((string)$vSubSubElement['type']=='generic') {
									$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'_reward_generic_coins","'.(string)$vSubSubElement['coins'].'");');
									$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'_reward_generic_experience","'.(string)$vSubSubElement['experience'].'");');
								}
								if((string)$vSubSubElement['type']=='crecipe') {
									$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'_reward_crecipe_recipeId","'.(string)$vSubSubElement['recipeId'].'");');
									$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","completionRequirements_'.$vCompleteName.'_reward_crecipe_quantity","'.(string)$vSubSubElement['quantity'].'");');
								}
							}
						}
					} else {
						$vDataDB->queryExec('insert into quests(name,field,content) values("'.$vQuestID.'","'.$vSubName.'","'.(string)$vSubElement.'");');
					}
				}
			}
		}
		unset($xmlDoc);

		$vDataDB->queryExec('COMMIT TRANSACTION');

		$vCollectable=array();
		#create collectable_info.txt
		$vSQL='select * from collectables where field="code"';
		$vResult = $vDataDB->query($vSQL);
		while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
			$vCollectable[$vRow['content']]['name']=$vRow['name'];
			$vCollectable[$vRow['content']]['code']=$vRow['content'];
			$vSQL2='select content from collectables where name="'.$vRow['name'].'" and field="tradeInReward_xp"';
			$vResult2 = $vDataDB->query($vSQL2);
			$vCollectable[$vRow['content']]['tradeInReward']=$vResult2->fetchSingle();
			$vSQL2='select content from collectables where name="'.$vRow['name'].'" and field="realname"';
			$vResult2 = $vDataDB->query($vSQL2);
			$vCollectable[$vRow['content']]['realname']=$vResult2->fetchSingle();
			$vSQL2='select content from collectables where name="'.$vRow['name'].'" and field="collectable"';
			$vResult2 = $vDataDB->query($vSQL2);
			while ($vRow2 = $vResult2->fetch(SQLITE_ASSOC)) {
				$vCollectable[$vRow['content']]['collectable'][]=$vRow2['content'];
			}
		}
		file_put_contents('collectable_info.txt',serialize($vCollectable));
		unset($vCollectable);

		$vAchievements=array();
		$vSQL='select * from achievements where field="code"';
		$vResult = $vDataDB->query($vSQL);
		while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
			$vAchievements[$vRow['content']]['name']=$vRow['name'];
			$vAchievements[$vRow['content']]['code']=$vRow['content'];
			$vSQL2='select content from achievements where name="'.$vRow['name'].'" and field="iconurl"';
			$vResult2 = $vDataDB->query($vSQL2);
			$vAchievements[$vRow['content']]['iconurl']=$vResult2->fetchSingle();
			$vSQL2='select content from achievements where name="'.$vRow['name'].'" and field="realname"';
			$vResult2 = $vDataDB->query($vSQL2);
			$vAchievements[$vRow['content']]['realname']=$vResult2->fetchSingle();
			$vSQL2='select content from achievements where name="'.$vRow['name'].'" and field="desc"';
			$vResult2 = $vDataDB->query($vSQL2);
			$vAchievements[$vRow['content']]['desc']=$vResult2->fetchSingle();

			$vSQL2='select content from achievements where name="'.$vRow['name'].'" and field="count" order by field';
			$vResult2 = $vDataDB->query($vSQL2);
			while ($vRow2 = $vResult2->fetch(SQLITE_ASSOC)) {
				$vAchievements[$vRow['content']]['level'][]=$vRow2['content'];
			}
		}
		file_put_contents('achievement_info.txt',serialize($vAchievements));
		unset($vAchievements);

		file_put_contents('units.txt',serialize(Units_GetAll()));

		file_put_contents('sqlite_check.txt',$flashRevision);
	}
	sleep(10);
	EchoData('OK');
}
// ------------------------------------------------------------------------------
// RunHttp. Now only parses the GET parameters. HTTP server is moved to farmvillebot.exe
// ------------------------------------------------------------------------------
function RunHttp() {
		global $plugins,$userId,$botlitever,$plugin_developer;

		if (! @trim(file_get_contents('developer.txt')))
		error_reporting(E_ERROR);

		$argv = @$GLOBALS['argv'];
		$userId = @$argv[2];

		echo "userId $userId;\r\n";

		define ('farmer', GetFarmserver());
		define ('farmer_url', GetFarmUrl());

		$request_uri = @$argv[4];
		$postdata = urldecode(@$argv[6]);

		if ($request_uri) {
				preg_match('/\/plugins\/(.*)\/main.php[\?]*(.*)$/si', $request_uri, $match);
				$plugin_name = @$match[1];
				$params_str = @$match[2];

				if ($plugin_name) {
						$_GET = array();
						$params = explode('&', $params_str);
						foreach($params as $param) {
								list($key, $value) = explode('=', $param);
								$value = urldecode($value);
								if (isset($_GET[$key])) {
										if (!is_array($_GET[$key])) {
												$tmp = $_GET[$key];
												$_GET[$key] = array();
												$_GET[$key][] = $tmp;
										}
										$_GET[$key][] = $value;
								} else
								$_GET[$key] = $value;
						}
						$_POST = array();
						$postparams = explode('&', $postdata);
						foreach($postparams as $param) {
								list($key, $value) = explode('=', $param);
								$value = urldecode($value);
								if (isset($_POST[$key])) {
										if (!is_array($_POST[$key])) {
												$tmp = $_POST[$key];
												$_POST[$key] = array();
												$_POST[$key][] = $tmp;
										}
										$_POST[$key][] = $value;
								} else
								$_POST[$key] = $value;
						}
				}
				// find form function
				$form_function = $plugin_name . '_form';
				global $this_plugin;

				foreach ($plugins as $plugin)
				{
						if ($plugin['name'] == $plugin_name) {
								$this_plugin = $plugin;
								break;
						}
				}
				ob_clean();
				//ob_start();
				//We have a function_form() use it
				if (function_exists($form_function)) {
						call_user_func($form_function);
				}
				$request_uri = substr($request_uri,1);

				//Objects Don't need to call the form function
				if ($plugin_name == '') {
						if (stripos($request_uri,'.php')) {
								include($request_uri);
						} else {
								readfile($request_uri);
						}
						//CSS
						if (stripos($request_uri,'.css')) $GLOBALS['http_headers'] = "Content-Type: text/css\r\n";
						//IMAGES
						preg_match('/(png|tiff|gif|jpeg|jpg)/i', $request_uri, $matches);
						$matches[1] = (@$matches[1] == 'jpg') ? 'jpeg' : @$matches[1];
						if ($matches[1] != '') $GLOBALS['http_headers'] = "Content-Type: image/" . $matches[1] . "\r\n";
						//JavaScript
						if (stripos($request_uri,'.js')) $GLOBALS['http_headers'] = "Content-Type: application/javascript\r\n";
						if (stripos($request_uri,'.php') || stripos($request_uri,'.html')) $GLOBALS['http_headers'] = "Content-Type: text/html\r\n";
				}
				$contents = ob_get_contents();
				$length = strlen($contents);
				ob_end_clean();
				if (isset($GLOBALS['http_headers'])) {
						echo("HTTP/1.1 200 OK\r\nContent-Length: " . $length . "\r\n" . $GLOBALS['http_headers'] . "\r\n");
						#AddLog2($GLOBALS['http_headers']);
						unset($GLOBALS['http_headers']);
				} else {
						echo("HTTP/1.1 200 OK\r\nContent-Length: " . $length . "\r\nContent-Type: text/html\r\n\r\n");
				}
				echo $contents;
		}
}
function GetTransforms($array) {
	$ret = array();
	foreach ($array as $item) {
		if (@$item['action'] == "transform") {
			$ret[] = $item['name'];
		}
	}
	return $ret;
}
// ------------------------------------------------------------------------------
// Hook
// ------------------------------------------------------------------------------
function Hook($hook) {
	global $plugins;
	global $this_plugin;
	foreach ($plugins as $plugin) {
		if (isset($plugin['hooks'][$hook])) {
			if (function_exists($plugin['hooks'][$hook])) {
				$this_plugin = $plugin;
				call_user_func($plugin['hooks'][$hook]);
			}
		}
	}
}
// ------------------------------------------------------------------------------
// GetSecretKey gets userId and flashRevision
// ------------------------------------------------------------------------------
function GetSecretKey() {
	$datasize = hexdec(fread(STDIN, 8));
	$data = fread(STDIN, $datasize);

	$amf = new AMFObject($data);
	$deserializer = new AMFDeserializer($amf->rawData);
	$deserializer->deserialize($amf);

	if (isset($amf->_bodys[0]->_value[0]['token'])) {
		$str = $amf->_bodys[0]->_value[0]['masterId'] . "\r\n" . $amf->_bodys[0]->_value[0]['flashRevision'];

		global $userId, $flashRevision, $token;
		$userId = $amf->_bodys[0]->_value[0]['masterId'];
		$flashRevision = $amf->_bodys[0]->_value[0]['flashRevision'];
		$token = $amf->_bodys[0]->_value[0]['token'];
		SaveAuthParams();

		EchoData($str);
	} else {
		RaiseError(1);
	}
}
// ------------------------------------------------------------------------------
// GetSecretKeyLite gets userId and flashRevision
// ------------------------------------------------------------------------------
function GetSecretKeyLite() {
	global $userId, $flashRevision, $token;
	$argv = @$GLOBALS['argv'];
	$userId = @$argv[2];
	$flashRevision = @$argv[3];
	$token = @$argv[4];
	SaveAuthParams();
}
// ------------------------------------------------------------------------------
// Exported functions
// ------------------------------------------------------------------------------
// ------------------------------------------------------------------------------
// load_array
// ------------------------------------------------------------------------------
function load_array($filename) {
	global $this_plugin;
	return @unserialize(file_get_contents($this_plugin['folder'] . '/' . PluginF($filename)));
}
// ------------------------------------------------------------------------------
// save_array
// ------------------------------------------------------------------------------
function save_array($array, $filename) {
	global $this_plugin;
	file_put_contents($this_plugin['folder'] . '/' . PluginF($filename),serialize($array));
}
// ------------------------------------------------------------------------------
// AddLog add string to main log
//  @params string $str Text
// ------------------------------------------------------------------------------
function AddLog($str) {
	global $is_debug;
	global $res_str;
	$res_str .= $str . "\r\n";

	if ($is_debug) echo $str;
}
// ------------------------------------------------------------------------------
// AddLog2 add string to advanced log
//  @params string $str Text
// ------------------------------------------------------------------------------
function AddLog2($str) {
	global $is_debug, $consolelog;
	@file_put_contents(LogF("log2.txt"),@date("H:i:s")." $str\r\n",FILE_APPEND);
	if ($is_debug || $consolelog)
	echo "Log2: " . $str . "\r\n";
}
// ------------------------------------------------------------------------------
// DebugLog
//  @params string $str Text
// ------------------------------------------------------------------------------
function DebugLog($str) {
	global $is_debug;
	if ($is_debug)
		echo $str . "\r\n";
}
// ------------------------------------------------------------------------------
// F creates a full file name
//  @param string $filename Short file name
//  @return string Full file name (UserID + '_' + Short name)
// ------------------------------------------------------------------------------
function F($filename) {
	global $userId;

	if ($filename == "units.txt") {
		return "units.txt";
	} //pre2.10 plugin support
	$folder = "FBID_" . $userId;
	if (!is_dir($folder)) {
		@mkdir($folder);
	}

	return $folder . '/' . $filename;
}
// ------------------------------------------------------------------------------
// PluginF creates a full file name (original F())
//  @param string $filename Short file name
//  @return string Full file name (UserID + '_' + Short name)
// ------------------------------------------------------------------------------
function PluginF($filename) {
	global $userId;
	return $userId . '_' . $filename;
}

function LogF($filename) {
	global $userId;
	return $userId . '_' . $filename;
}
// ------------------------------------------------------------------------------
// GetNeighbors gets a list of neighbors
//  @return array List of neighbors
// ------------------------------------------------------------------------------
function GetNeighbors() {
	DebugLog(" >> GetNeighbors");
	$neighborsstr = file_get_contents(F('neighbors.txt'));
	$neighbors = unserialize($neighborsstr);

	DebugLog(" << GetNeighbors");
	return $neighbors;
}
// ------------------------------------------------------------------------------
// GetObjects gets a list of objects on the farm
//  @param string $className Class name ('Plot', 'Animal', 'Tree' etc.)
//  @return array List of objects
// ------------------------------------------------------------------------------
function GetObjects($className = '') {
	// FarmFIX
	$my_farm_is_fucked_up = 0;
	if (file_exists('farmfix.txt')) {
		$my_farm_is_fucked_up = trim(file_get_contents('farmfix.txt'));
	}
	if ($my_farm_is_fucked_up == 1) {
		return GetObjects2($className);
	}
	// FarmFIX
	DebugLog(" >> GetObjects");
	$objectsstr = file_get_contents(F('objects.txt'));
	$objects = unserialize($objectsstr);

	if ($className) {
		$resobjects = array();
		foreach ($objects as $object)
		if ($object['className'] == $className)
			$resobjects[] = $object;
		DebugLog(" << GetObjects");
		return $resobjects;
	} else {
		DebugLog(" << GetObjects");
		return $objects; //return all objects
	}
}
// ------------------------------------------------------------------------------
// GetObjects2 gets a list of objects on the farm from 2 object file _1 and _2
//  @param string $className Class name ('Plot', 'Animal', 'Tree' etc.)
//  @return array List of objects
// ------------------------------------------------------------------------------
function GetObjects2($className = '') {
	$object_file_1_str = file_get_contents(F('objects_1.txt'));
	$object_file_1 = unserialize($object_file_1_str);

	$object_file_2_str = file_get_contents(F('objects_2.txt'));
	$object_file_2 = unserialize($object_file_2_str);

	if ($className) {
		$resobjects = array();

		foreach ($object_file_1 as $object)
		if ($object['className'] == $className)
			$resobjects[] = $object;

		foreach ($object_file_2 as $object)
		if ($object['className'] == $className)
			$resobjects[] = $object;

		return $resobjects;
	} else {
		foreach ($object_file_1 as $object)
		$obj_joined[] = $object;

		foreach ($object_file_2 as $object)
		$obj_joined[] = $object;

		return $obj_joined; //return all objects
	}
}
// ------------------------------------------------------------------------------
// GetPlotName compiles plot name
//  @param array $plot
//  @return string Plot name
// ------------------------------------------------------------------------------
function GetPlotName($plot) { return $plot['position']['x'] . '-' . $plot['position']['y']; }

// ------------------------------------------------------------------------------
// Do_Farmhands_Arborists
// ------------------------------------------------------------------------------
function Do_Farmhands_Arborists($vWhat) {
	global $userId;
	global $vCnt63000;
	if($vCnt63000<63000) $vCnt63000=63000;

	$amf = CreateRequestAMF();

	$amf->_bodys[0]->_value[1][0]['functionName'] = 'WorldService.performAction';

	$amf->_bodys[0]->_value[1][0]['params'][0] = 'use';

	$amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = -1;
	if($vWhat=='farmhands') {
		$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CHarvestAnimals';
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = 'consume_farm_hands';
	} else {
		$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CHarvestTrees';
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = 'consume_arborists';
	}
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $vCnt63000 ++;

	$amf->_bodys[0]->_value[1][0]['params'][2]['targetUser'] = $userId;
	$amf->_bodys[0]->_value[1][0]['params'][2]['isFree'] = false;
	$amf->_bodys[0]->_value[1][0]['params'][2]['storageID'] = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2]['isGift'] = true;

	$res=RequestAMF($amf);

	if($res=='OK') {
		AddLog($vWhat." OK");
		AddLog2($vWhat." OK");
	} else {
		AddLog($vWhat." error: ".$res);
		AddLog2($vWhat." error: ".$res);
	}
	return true;

}

// ------------------------------------------------------------------------------
// Do_Biplane_Instantgrow
// ------------------------------------------------------------------------------
function Do_Biplane_Instantgrow() {
	$biplane = GetObjects('Airplane');
	if(count($biplane)==0) {
		AddLog2("no biplane found");
		AddLog("no biplane found");
		return false;
	}
	$biplane=$biplane[0];

	$amf = CreateRequestAMF();
	$amf->_bodys[0]->_value[1][0]['functionName'] = 'WorldService.performAction';
	$amf->_bodys[0]->_value[1][0]['params'][0] = 'instantGrow';
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = 'NaN';
	$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = $biplane['className'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['state'] = $biplane['state'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $biplane['itemName'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $biplane['id'];
	$amf->_bodys[0]->_value[1][0]['params'][1]['position'] = $biplane['position'];
	$amf->_bodys[0]->_value[1][0]['params'][2] = array();

	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);

	if($res=='OK') {
	  $vSucess = $amf2->_bodys[0]->_value['data'][0]['success'];
	  $vCost = $amf2->_bodys[0]->_value['data'][0]['cost'];
	  if($vSucess=="1") {
		AddLog("biplane success, COST: ".$vCost." CASH");
		AddLog2("biplane success, COST: ".$vCost." CASH");
	  } else {
		AddLog("biplane error: ".implode($amf2->_bodys[0]->_value['data'][0]));
		AddLog2("biplane error: ".implode($amf2->_bodys[0]->_value['data'][0]));
	  }
	} else {
		AddLog("biplane error: ".$res);
		AddLog2("biplane error: ".$res);
	}
	return $res;

}

// ------------------------------------------------------------------------------
// Do_Check_Lonlyanimals
// ------------------------------------------------------------------------------
function Do_Check_Lonlyanimals() {

	$amf = CreateRequestAMF();

	$amf->_bodys[0]->_value[1][0]['functionName'] = 'LonelyCowService.createLonelyAnimal';
	$amf->_bodys[0]->_value[1][0]['params'][0] = array();
	$amf->_bodys[0]->_value[2] = 0;

	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);

	if($res=='OK') {
	  $vAnimal = $amf2->_bodys[0]->_value['data'][1];
	  if(strlen($vAnimal)>0) {
		AddLog("lonlyanimal found: ".$vAnimal);
		AddLog2("lonlyanimal found: ".$vAnimal);
	  } else {
		AddLog2("no lonlyanimal found");
	  }
	} else {
		AddLog2("lonlyanimal error: ".$res);
	}
	return $res;

}

// ------------------------------------------------------------------------------
// Do_Check_Wanderinganimals
// ------------------------------------------------------------------------------
function Do_Check_Wanderinganimals() {

	$amf = CreateRequestAMF();

	$amf->_bodys[0]->_value[1][0]['functionName'] = 'WanderingAnimalService.onCreateStallionReward';
	$amf->_bodys[0]->_value[1][0]['params'][0] = array();
	$amf->_bodys[0]->_value[2] = 0;

	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);

	if($res=='OK') {
	  $vReward = $amf2->_bodys[0]->_value['data'][0]['data']['rewardUrl'];
	  if(strlen($vReward)>0) {
		AddLog("wanderinganimal found");
		AddLog2("wanderinganimal found");
	  } else {
		AddLog2("no wanderinganimal found");
	  }
	} else {
		AddLog2("wanderinganimal error: ".$res);
	}
	return $res;

}

// ------------------------------------------------------------------------------
// Do_Accept_Neighbor_Help
// ------------------------------------------------------------------------------
function Do_Accept_Neighbor_Help() {
	global $userId;
	$vData=array();
	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] > 50) || (@$px_Setopts['bot_speed'] < 1)) {
		$vSpeed = 1;
	} else {
		$vSpeed=$px_Setopts['bot_speed'];
	}
	$vWorld=unserialize(file_get_contents(F('world.txt'))); //get the last world
	$vNActions = $vWorld['data'][0]['data']['userInfo']['player']['neighborActionQueue']['m_actionQueue'];
	foreach($vNActions as $vActions) {
		$vNID = $vActions['visitorId'];
		foreach ($vActions['actions'] as $vAction) {
			$vData[]=array(0=>$vNID,1=>$vAction['actionType'],2=>$vAction['objectId']);
		}
	}
	while(count($vData)>0) {
		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();

		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';

		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;

		$vCntSpeed=0;
		while(count($vData)>0 && $vCntSpeed<$vSpeed) {
			$vParams=array_pop($vData);
			$amf->_bodys[0]->_value[1][$vCntSpeed]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vCntSpeed]['functionName'] = "NeighborActionService.clearNeighborAction";
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'] = $vParams;
			if (@!$OKstring)
				$OKstring = 'accept help '.$vParams[1].' from '.$vParams[0].' on plot '.$vParams[2];
			else
				$OKstring = $OKstring."\r\n".'accept help '.$vParams[1].' from '.$vParams[0].' on plot '.$vParams[2];
			$vCntSpeed++;
		}

		$res = RequestAMF($amf);
		unset($amf->_bodys[0]->_value[1]);
		$need_reload = true;

		if ($res === 'OK') {
			AddLog($OKstring);
			AddLog2('accept neighbor help: OK');
		} else {
			if ($res) {
				AddLog("Error: $res on accept neighbor help");
				AddLog2("Error: $res on accept neighbor help");
				if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) { // Server sequence was reset
					DoInit();
				}
			}
		}
		unset($OKstring);
	}
}

// ------------------------------------------------------------------------------
// Do_Farm_Work
//  @param array $plots
//  @param string $action (optional)
// ------------------------------------------------------------------------------
function Do_Farm_Work($plots, $action = "harvest") {
	global $need_reload;
	$px_Setopts = LoadSavedSettings();

	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1))
		$px_Setopts['bot_speed'] = 1;

	if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED)
		$px_Setopts['bot_speed'] = PARSER_MAX_SPEED;

	$count = count($plots);

	if ($count > 0) {
		global $userId;
		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();

		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';

		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;
		$i = 0;

		foreach($plots as $plot) {
			$amf->_bodys[0]->_value[1][$i]['functionName'] = "WorldService.performAction";
			$amf->_bodys[0]->_value[1][$i]['params'][0] = $action;
			$amf->_bodys[0]->_value[1][$i]['sequence'] = GetSequense();

			$amf->_bodys[0]->_value[1][$i]['params'][1] = $plot;
			$amf->_bodys[0]->_value[1][$i]['params'][2] = array();

			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['energyCost'] = 0;

			if (@!$plotsstring)
				$plotsstring = $plot['itemName'] . " " . GetPlotName($plot);
			else
				$plotsstring = $plotsstring . ", " . $plot['itemName'] . " " . GetPlotName($plot);

			if (@!$OKstring)
				$OKstring = $action . " " . $plot['itemName'] . " on plot " . GetPlotName($plot);
			else
				$OKstring = $OKstring . "\r\n" . $action . " " . $plot['itemName'] . " on plot " . GetPlotName($plot);

			$i++;

			if (($i == $px_Setopts['bot_speed']) || ($i >= $count)) {
				$count -= $i;
				$i = 0;
				AddLog2($action . " " . $plotsstring);
				$res = RequestAMF($amf);
				AddLog2("result $res");
				unset($amf->_bodys[0]->_value[1]);
				$need_reload = true;

				if ($res === 'OK') {
					AddLog($OKstring);
				} else {
					if ($res) {
						AddLog("Error: $res on " . $OKstring);
						if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) { // Server sequence was reset
							DoInit();
						}
					}
				}
				unset($plotsstring, $OKstring);
			}
		}

		SaveAuthParams();
	}
}

// ------------------------------------------------------------------------------
// Do_Farm_Work_Plots
//  @param array $plots
//  @param string $action (optional)
// ------------------------------------------------------------------------------
function Do_Farm_Work_Plots($plots, $action = "harvest") {
	global $need_reload;
	global $vCnt63000;
	if(@strlen($vCnt63000)==0) $vCnt63000=63000;
	$px_Setopts = LoadSavedSettings();

	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1))
		$px_Setopts['bot_speed'] = 1;

	if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED)
		$px_Setopts['bot_speed'] = PARSER_MAX_SPEED;

	$vMaxEquip=16;

	if ($action == 'combine')
		$fuel = @$px_Setopts['fuel_combine'];

	if ($action == 'tractor')
		$fuel = @$px_Setopts['fuel_plow'];

	if ($action == 'plow')
		$fuel = @$px_Setopts['fuel_plow'];

	if ($action == 'place')
		$fuel = @$px_Setopts['fuel_place'];

	if ($action == 'harvest')
		$fuel = @$px_Setopts['fuel_harvest'];

	if ((@!$fuel) || (@$fuel < 0))
		$fuel = 0;

	if ($fuel == 0 && $action == 'combine') {
		return;
	}
	if ($fuel == 0 && $action == 'tractor') {
		return;
	}
	if ($fuel == 0) {
		Do_Farm_Work($plots, $action);
		return;
	}

	while(count($plots)>0) {
		global $userId;
		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();

		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';

		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;

		$vCntSpeed=0;
		while(count($plots)>0 && $vCntSpeed<$px_Setopts['bot_speed'] && $fuel>0) {
			$amf->_bodys[0]->_value[1][$vCntSpeed]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vCntSpeed]['functionName'] = "EquipmentWorldService.onUseEquipment";
			if ($action == 'tractor') {
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][0] = 'plow';
			} else {
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][0] = $action;
			}

			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['id'] = -1;
			if ($action == 'combine')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'q1:96';  # fully expanded combine
			if ($action == 'harvest')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'V1:32';  # fully expanded harvester
			if ($action == 'tractor')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'T1:32';  # fully expanded tractor
			if ($action == 'plow')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'T1:32';  # fully expanded tractor
			if ($action == 'place')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'S1:32';  # fully expanded seeder

			$vCntEquip=0; $vSeed=''; $vLastSeed='';
			while(count($plots)>0 && $vCntEquip<$vMaxEquip && $fuel>0) {
				$vPlot=array_pop($plots);
				if ($action == 'place' || $action == 'combine') {
					$vSeed=$vPlot['itemName'];
					if($vLastSeed=='') {
						$vLastSeed=$vSeed;
					} elseif($vLastSeed<>$vSeed) {
						array_push($plots,$vPlot);
						break;
					}
				}

				if (@!$plotsstring)
					$plotsstring = $vPlot['itemName'] . " " . GetPlotName($vPlot);
				else
					$plotsstring = $plotsstring . ", " . $vPlot['itemName'] . " " . GetPlotName($vPlot);

				if (@!$OKstring)
					$OKstring = $action . " " . $vPlot['itemName'] . " on plot " . GetPlotName($vPlot);
				else
					$OKstring = $OKstring . "\r\n" . $action . " " . $vPlot['itemName'] . " on plot " . GetPlotName($vPlot);

				$fuel--;
				if ($action == 'tractor') {
#					$vCnt63000++;
#					$vPlot['id'] = $vCnt63000;
					$vPlot['id'] = $vCnt63000 ++;
					$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][2][$vCntEquip] = $vPlot;
				} else {
					$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][2][$vCntEquip]['id'] = $vPlot['id'];
				}

				$vCntEquip++;

			}

			if ($action == 'combine')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = $vSeed;
			if ($action == 'tractor')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'harvest')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'plow')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'place')
				$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = $vSeed;

			$vCntSpeed++;
		}

		AddLog2($action . " " . $plotsstring);

		$res = RequestAMF($amf);
		AddLog2("result $res");
		unset($amf->_bodys[0]->_value[1]);
		$need_reload = true;

		if ($res === 'OK') {
			AddLog($OKstring);
		} else {
			if ($res) {
				AddLog("Error: $res on " . $OKstring);
				if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) { // Server sequence was reset
					DoInit();
				}
			}
		}
		unset($plotsstring, $OKstring);

	}

	$px_Setopts = LoadSavedSettings();

	if ($action == 'combine') $px_Setopts['fuel_combine'] = $fuel;
	if ($action == 'tractor') $px_Setopts['fuel_plow'] = $fuel;
	if ($action == 'plow') $px_Setopts['fuel_plow'] = $fuel;
	if ($action == 'place') $px_Setopts['fuel_place'] = $fuel;
	if ($action == 'harvest') $px_Setopts['fuel_harvest'] = $fuel;

	SaveSettings($px_Setopts);
	SaveAuthParams();
}

function RequestAMF($amf) {
	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);
	return $res;
}

// ------------------------------------------------------------------------------
// RequestAMFIntern sends AMF request to the farmville server
//  @param object $request AMF request
//  @return object $amf2
// ------------------------------------------------------------------------------
function RequestAMFIntern($amf) {
	DebugLog(" >> RequestAMF");

	$serializer = new AMFSerializer();
	if(function_exists('curl_exec')) $answer = $GLOBALS['curlfetcher']->post(farmer_url, $serializer->serialize($amf), 'application/x-amf');
	else {
		$result = $serializer->serialize($amf); // serialize the data
		$s = Connect();
		$answer = Request($s, $result);
		fclose($s);
	}
	$amf2 = new AMFObject($answer);
	$deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
	$deserializer2->deserialize($amf2); // run the deserializer
	DebugLog(" << RequestAMF");

	CheckAMF2Rewards($amf2);
	return $amf2;
}


// ------------------------------------------------------------------------------
// CheckAMF2Response check
//  @param object $request AMF2 response
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function CheckAMF2Response($amf2) {
	if (@$amf2->_bodys[0]->_value['errorType'] != 0) {
		if ($amf2->_bodys[0]->_value['errorData'] == "There is a new version of the farm game released") {
			AddLog2("New version of the game released");
			echo "\n*****\nGame version out of date\n*****\n";
			unlink('sqlite_check.txt');
			RestartBot();
		} else if ($amf2->_bodys[0]->_value['errorData'] == "Client has a newer version than backend") {
			AddLog2("Client has a newer version than backend");
			echo "\n*****\nGame version out of date\n*****\n";
			unlink('sqlite_check.txt');
			RestartBot();
		} else if ($amf2->_bodys[0]->_value['errorData'] == "token value failed") {
			AddLog2("Error: token value failed");
			AddLog2("You opened the game in another browser");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: token value failed\nThis error is caused by opening the game in another browser\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		} else if ($amf2->_bodys[0]->_value['errorData'] == "token too old") {
			AddLog2("Error: token too old");
			AddLog2("The session expired");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: token too old\nThe session has expired\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		} else if ($amf2->_bodys[0]->_value['errorType'] == 29) {
			AddLog2("Error: Server sequence was reset");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: Server sequence was reset\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		} else if ($amf2->_bodys[0]->_value['errorType'] == 27) {
			AddLog2("Error: User session not in memcache");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: User session not in memcache\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		} else {
			echo "\n*****\nError: \n" . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'] . "\n";
			$res = "Error: " . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'];
		}
	} else if (!isset($amf2->_bodys[0]->_value['data'][0])) {
		echo "\n*****\nError:\n BAD AMF REPLY - Possible Server problem or farm badly out of sync\n*****\n";
		$res = "BAD AMF REPLY (OOS?)";
	} else if (isset($amf2->_bodys[0]->_value['data'][0]['data']) && ($amf2->_bodys[0]->_value['data'][0]['data']=='success')) {
		$res = 'OK';
	 } else if (isset($amf2->_bodys[0]->_value['data'][0]['data']) && ($amf2->_bodys[0]->_value['data'][0]['data']=='6uccess')) {
		$res = 'OK';
	} else if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] <> 0)) {
		$res = $amf2->_bodys[0]->_value['data'][0]['errorType'] . " " . $amf2->_bodys[0]->_value['data'][0]['errorData'].' '.$amf2->_bodys[0]->_value['data'][0]['data']['error'];
	} else if (isset($amf2->_bodys[0]->_value['data'][0]['data']['error']) && (strlen($amf2->_bodys[0]->_value['data'][0]['data']['error'])>0)) {
		$res = $amf2->_bodys[0]->_value['data'][0]['data']['error'];
	} else {
		$res = 'OK';
	}
	return $res;
}


function CheckAMF2RewardsSubCheck($vRewURL,$vRewItem,$vItemUrl,&$vFound,&$vRewardsArray) {
	if(strlen($vRewURL)>0 && strlen($vRewItem)>0 && substr($vRewURL,0,10)=='reward.php' && strpos($vRewURL,' ')===false) {
		$vRewardsArray[]=array('rewardLink' => $vRewURL, 'rewardItem' => $vRewItem, 'timestamp' => time());
		$vFound[]=$vRewItem;
	}
}
function CheckAMF2RewardsSubCheck2($vRewURL,$vRewItem,$vItemUrl,&$vFound,&$vRewardsArray) {
	if(strlen($vRewURL)>0 && substr($vRewURL,0,10)=='reward.php' && strpos($vRewURL,' ')===false && strpos($vRewURL,$vRewItem)!==false) {
		$vRewardsArray[]=array('rewardLink' => $vRewURL, 'rewardItem' => $vRewItem, 'timestamp' => time());
		$vFound[]=$vRewItem;
	}
}


function CheckAMF2RewardsSub($vReward,&$vFound,&$vRewardsArray) {
	CheckAMF2RewardsSubCheck($vReward['collectionCounters'][0]['link'],$vReward['collectionCounters'][0]['collectable'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'],$vReward['data']['animalName'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'],$vReward['data']['rewardItem'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'],$vReward['data']['rewardType'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'],$vReward['data']['animalName'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'],$vReward['data']['bonusCoins'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'],$vReward['data']['gift'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'],$vReward['data']['harvestItem'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'],$vReward['data']['rewardItem'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['itemFoundRewardUrl'],$vReward['data']['itemShareName'],'Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['data']['rewardLink'],$vReward['data']['data']['rewardItem'],'Item',$vFound,$vRewardsArray);

	CheckAMF2RewardsSubCheck($vReward['collectionCounters']['0']['link'],$vReward['collectionCounters']['0']['collectable'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data'][0]['rewardLink'],$vReward['data'][0]['recipeId'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['buyResponse']['buyResults'][0]['rewardLink'],$vReward['data']['buyResponse']['buyResults'][0]['recipe'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['data']['fuelRewardLink'],'2A','Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['fuelRewardLink'],'2A','Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['data']['fuelDiscoveryRewardLink'],'2A','Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['fertilizeRewardLink'],$vReward['data']['goodieBagRewardItemCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['foalRewardLink'],$vReward['data']['foalCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['foundBushel']['bushelsFullRewardUrl'],$vReward['data']['foundBushel']['bushelCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['foundBushel']['openStallRewardUrl'],$vReward['data']['foundBushel']['bushelCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['itemBuffRewardUrl'],$vReward['data']['itemBuffCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['mysterySeed']['rewardLink'],$vReward['data']['mysterySeed']['itemCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'],$vReward['data']['itemCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'],$vReward['data']['itemCode'],'Code',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['goals'][0]['link'],$vReward['goals'][0]['code'],'Code',$vFound,$vRewardsArray);

	CheckAMF2RewardsSubCheck2($vReward['data']['rewardLink'],'PigpenSlopFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'ValentineRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'PotOfGoldRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'EasterBasketRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'TuscanWeddingRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'CellarRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'HaitiBackpackRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'HalloweenBasketRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'thanksgivingbasketRedeemFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'BushelFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'WanderingStallionFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'],'OilBarronFriendReward','Item',$vFound,$vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardLink'],'ConstructionBuildingFriendReward','Item',$vFound,$vRewardsArray);
}


function CheckAMF2Rewards($amf2) {
	if(!isset($amf2->_bodys[0]->_value['data'])) return;
	if(isset($amf2->_bodys[0]->_value['data']['0']['data']['userInfo'])) return;

	$vRewardsArray=array();
	$vRewardsArrayNew=array();
	$vFound=array();
	$vSeen=array();
	foreach($amf2->_bodys[0]->_value['data'] as $vReward) {
		$vFound=false;
		if(stripos(print_r($vReward,true), 'reward.php') === false) continue;
		CheckAMF2RewardsSub($vReward,$vFound,$vRewardsArray);
		if(is_array($vReward['data']['harvest']['data'])) {
			foreach($vReward['data']['harvest']['data'] as $vSubReward) {
				if(stripos(print_r($vSubReward,true), 'reward.php') === false) continue;
				CheckAMF2RewardsSub($vSubReward,$vFound,$vRewardsArray);
			}
		}
		if(is_array($vReward['data']['plow']['data'])) {
			foreach($vReward['data']['plow']['data'] as $vSubReward) {
				if(stripos(print_r($vSubReward,true), 'reward.php') === false) continue;
				CheckAMF2RewardsSub($vSubReward,$vFound,$vRewardsArray);
			}
		}
		if(is_array($vReward['data']['place']['data'])) {
			foreach($vReward['data']['place']['data'] as $vSubReward) {
				if(stripos(print_r($vSubReward,true), 'reward.php') === false) continue;
				CheckAMF2RewardsSub($vSubReward,$vFound,$vRewardsArray);
			}
		}
		if(is_array($vReward['data'][0]['data'])) {
			foreach($vReward['data'] as $vSubReward) {
				if(stripos(print_r($vSubReward,true), 'reward.php') === false) continue;
				CheckAMF2RewardsSub($vSubReward,$vFound,$vRewardsArray);
			}
		}
		if(!$vFound) {
			file_put_contents('rew_data_raw_'.date('z').'.txt', print_r($vReward,true));
			AddLog2('Parser_CheckAMF2Rewards: unknown reward found. check rew_data_raw_'.date('z').'.txt immediately, as it gets now overwritten!!');
			preg_match_all('/reward.php\?frHost=([^&]*)&frId=([^&]*)&frType=([^& ]*)/si', str_replace(array("\r","\n"),array(' ',' '),print_r($vReward,true)), $vRewards);
			for($vI = 0; $vI < count($vRewards[1]); $vI++) {
				$vUserID=$vRewards[1][$vI];
				$vRewID=$vRewards[2][$vI];
				$vRewType=$vRewards[3][$vI];
				if(strpos($vUserID.$vRewID.$vRewType,'"')!==false) continue;
				if(strpos($vUserID.$vRewID.$vRewType,'&')!==false) continue;
				if(strpos($vUserID.$vRewID.$vRewType,' ')!==false) continue;
				if(strpos($vUserID.$vRewID.$vRewType,'=')!==false) continue;
				$vRewURL='reward.php?frHost='.$vUserID.'&frId='.$vRewID.'&frType='.$vRewType;
				$vRewardsArray[]=array('rewardLink' => $vRewURL, 'rewardItem' => 'Unknown', 'timestamp' => time());
			}
		} else {
			AddLog2('Parser_CheckAMF2Rewards: rewards found: '.implode('|',$vFound));
		}
	}
	if(count($vRewardsArray)>0 && is_array($vRewardsArray)) {
		$vRewardsArrayOld = unserialize(file_get_contents(F('rewards.txt')));
		if(count($vRewardsArrayOld)>0) {
			foreach($vRewardsArrayOld as $vRewardTmp) {
				if(!in_array($vRewardTmp['rewardLink'],$vSeen) && substr($vRewardTmp['rewardLink'],0,10)=='reward.php' && strpos($vRewardTmp['rewardLink'],' ')===false && $vRewardTmp['timestamp']>(time()-(60*60*24))) {
					$vRewardsArrayNew[]=$vRewardTmp;
					$vSeen[]=$vRewardTmp['rewardLink'];
		}
	}
		}
		foreach($vRewardsArray as $vRewardTmp) {
			if(!in_array($vRewardTmp['rewardLink'],$vSeen) && substr($vRewardTmp['rewardLink'],0,10)=='reward.php' && strpos($vRewardTmp['rewardLink'],' ')===false && $vRewardTmp['timestamp']>(time()-(60*60*24))) {
				$vRewardsArrayNew[]=$vRewardTmp;
				$vSeen[]=$vRewardTmp['rewardLink'];
			}
		}
		save_botarray ($vRewardsArrayNew, F('rewards.txt'));
	}
}


function Parser_GetCookieString() {

	global $botlitever;
	if(isset($botlitever)){
		//in multiaccount-bot
		#cookies.txt
		if(file_exists(F('cookies.txt'))) {
			$cookiestr='';
			$vCookieArry=file(F('cookies.txt'));
			foreach($vCookieArry as $vCookieString) {
				$cookiestr.=substr($vCookieString,0,-16).'; ';
			}
		} else {
			AddLog2('Parser_GetCookieString: no cookies.txt');
		}
	} else {
		//in plain bot
		$retrunstring='';
		$vCookiepath='';
		if(is_file('cookiepath.txt')) {
			$vCookiepath=file_get_contents('cookiepath.txt');
		}
		if($vCookiepath=='default' || $vCookiepath==''){
			$last_modified = 0;
			$x = new COM("WScript.Shell");
			$cookiepath = $x->RegRead("HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Explorer\Shell Folders\Cookies");
		} else {
			$cookiepath = $vCookiepath;
		}
		$cookielist = scandir($cookiepath);
		foreach($cookielist as $cookiefile) {
			if (stripos($cookiefile, '@facebook[') !== false) {
				$filename = "$cookiepath/$cookiefile";
				if ($last_modified < filemtime($filename)) {
					$last_modified = filemtime($filename);
					$retrunstring = file_get_contents($filename);
				}
			}
		}
		$match = explode('*', $retrunstring);
		$cookiestr = '';
		foreach($match as $cookiedata) {
			$matchx = preg_split("/[\s]+/", $cookiedata);

			if ($matchx[0] != '')
				$cookiestr .= trim($matchx[0])."=".trim($matchx[1])."; ";
			else if ($matchx[1] != '')
				$cookiestr .= trim($matchx[1])."=".trim($matchx[2])."; ";
		}
	}
	return $cookiestr;
}

function Parser_ReadReq() {
	$vHTML=proxy_GET_FB('http://www.facebook.com/reqs.php');

	preg_match_all('/(<form rel="async" action="\/ajax\/reqs\.php" method="post".*?<\/form>)/ims', $vHTML, $vForms);
	unset($vHTML);

	$vGiftRequests = array();
	foreach($vForms[0] as $vI => $vForm) {
		preg_match_all('/name="([^"]*)" value="([^"]*)"/ims', $vForm, $vNameValues);

		preg_match_all('|<input[^>]*value="([^\"]*)"[^>]*name="actions\[([^>]*)][^>]*>?|ims', $vForm, $vActions);

		preg_match_all('|<a href="http://apps\.facebook\.com/.*?>(.*?)</a>|ims', $vForm, $vAppNameValues);

		preg_match_all('/name="fb_dtsg" value="([^"]*)"/ims', $vForm, $vDTSGValues);

		preg_match_all('|<span fb_protected="true" class="fb_protected_wrapper">(.*?)</span>|ims', $vForm, $vGiftText);
		$vGiftText = trim(strip_tags($vGiftText[1][0]));

		$vPost = '';
		$vAppId = '';
		for($vJ = 0; $vJ < count($vNameValues[1]); $vJ++) {
			if($vNameValues[1][$vJ] == 'params[app_id]')
				$vAppId = $vNameValues[2][$vJ];
			if($vPost != '')
				$vPost .= '&';
			$vPost .= $vNameValues[1][$vJ].'='.urlencode(html_entity_decode($vNameValues[2][$vJ], ENT_QUOTES, 'UTF-8'));
		}
		if($vAppId=='102452128776') {
			$vActionName = '';
			$vActionUrl = '';
			for($vJ = 0; $vJ < count($vActions[1]); $vJ++) {
				if($vActions[2][$vJ]!='reject') {
					$vActionName = $vActions[1][$vJ];
					$vActionUrl = html_entity_decode($vActions[2][$vJ]);
					$vPost .= '&actions['.urlencode(html_entity_decode($vActions[2][$vJ], ENT_QUOTES, 'UTF-8')).']='.str_replace('+', '%20', urlencode($vActions[1][$vJ]));
					break;
				}
			}
			$vAppName = '';
			if(count($vAppNameValues) > 0) {
				$vAppName = $vAppNameValues[1][0];
			}
			if(count($vDTSGValues) > 0) {
				$vDTSG = $vDTSGValues[1][0];
			}
			$vPost .= '&post_form_id_source=AsyncRequest';
			$vGiftRequests[$vI] = array(
				'form'=>$vForm,
				'name'=>'FarmVille',
				'app_id'=>$vAppId,
				'app_name'=>$vAppName,
				'action_name'=>$vActionName,
				'gift_text'=>$vGiftText,
				'action_url'=>$vActionUrl,
				'post_data'=>$vPost,
				'fb_dtsg'=>$vDTSG);
		}
		unset($vNameValues,$vActions,$vAppNameValues,$vDTSGValues,$vPost);
	}
	unset($vForms);
	return($vGiftRequests);
}

function pluginload() {
	// get list of plugins
	global $plugins, $userId, $flashRevision, $botlitever;
	$plugins = array();

	$dir = 'plugins';
	$dh = opendir($dir);

	if ($dh) {
		while (($file = readdir($dh)) !== false) {
			if (is_dir($dir . '/' . $file)) {
				if ($file != '.' && $file != '..') {
					$plugin = array();
					$plugin['name'] = $file;
					$plugin['folder'] = $dir . '/' . $file;
					$plugin['main'] = file_exists($dir . '/' . $file . '/main.php') ? $dir . '/' . $file . '/main.php' : '';
					$plugin['hooks'] = array();

					$plugins[] = $plugin;
				}
			}
		}
		closedir($dh);
	}

	global $hooks;
	global $this_plugin;
	// initialize plugins
	foreach ($plugins as $key => $plugin) {
		if ($plugin['main'] ) {
			// load plugin
			include($plugin['main']);
			// find init function
			$init_function = $plugin['name'] . '_init';
			if (function_exists($init_function)) {
				$hooks = array();
				$this_plugin = $plugin;
				// call init function
				call_user_func($init_function);
				if(!(file_exists('notrun_plugin_'.$plugin['name'].'.txt') || file_exists('notrun_plugin_'.$plugin['name'].'_'.$userId.'.txt'))) {
					$plugins[$key]['hooks'] = $hooks;
				}
			}
		}
	}
	if (PX_VER_PARSER != PX_VER_SETTINGS)
		echo "\r\n******\r\nERROR: PX's updated parser version (" . PX_VER_PARSER . ") doesn't match settings version (" . PX_VER_SETTINGS . ")\r\n******\r\n";
}


// ------------------------------------------------------------------------------
// Beginning of the script
// ------------------------------------------------------------------------------

//echo "----- begin parser.php v" . PX_VER_PARSER . " -----\r\n";

include_once(AMFPHP_BASE . "amf/io/AMFDeserializer.php");
include_once(AMFPHP_BASE . "amf/io/AMFSerializer.php");

$argv = @$GLOBALS['argv'];
$cmd = @$argv[1];

if ($plugin_developer) {
	pluginload();
}
// execute command
// echo("Command: ".$cmd."\r\n");
switch ($cmd) {
	case 'get_secret_key':
		echo "##### Getting Session Key #####\r\n";
		GetSecretKey();
		if (file_exists('auto_kill_parser.txt')) {
			echo "##### Killing Old php_farmvillebot.exe #####\r\n";
			$windir = $_ENV['windir'];
			if (is_file("Process.exe")) {
				exec("Process.exe -k php_farmvillebot*.exe");
			} //if this works the parser will be dead and wont process the else if/else
			elseif (is_file("$windir\\System32\\taskkill.exe")) {
				exec("$windir\\System32\\taskkill.exe /F /IM php_farmvillebot*");
			} //if this works the parser will be dead and wont process the else if/else
			elseif (is_file("$windir\\System32\\tskill.exe")) {
				exec("$windir\\System32\\tskill.exe php_farmvillebot*");
			}
			echo "If you can read this then your version of windows doesn't support killing the parser automatically. Sorry. Turning this feature off for you.\r\n"; //this should never happen
			unlink('auto_kill_parser.txt');
		}
		break;

	case 'get_unit_list_lite':
		global $botlitever;
		$botlitever = 1;
	case 'get_unit_list':
		echo "##### Loading units.txt #####\r\n";
		$work_timer_start = time();
		GetUnitList();
		$work_timer_end = time() - $work_timer_start;
		echo "##### GetUnitList completed in: $work_timer_end sec #####\r\n";
		break;

	case 'arbeit_lite':
		global $botlitever;
		$botlitever = 1;
	case 'arbeit':
		echo "##### Doing work #####\r\n";
		$work_timer_start = time();
		Arbeit();
		$work_timer_end = time() - $work_timer_start;
		echo "##### Work completed in: $work_timer_end sec #####\r\n";
	break;

	case 'run_http_lite':
		global $botlitever;
		$botlitever = 1;
	case 'run_http':
		ob_start();
		if (!$plugin_developer) {
			pluginload();
		}
		RunHttp();
		break;
	case 'get_secret_key_lite':
		global $botlitever;
		$botlitever = @$argv[5];
		if(strlen($botlitever)==0) $botlitever='unknown';
		echo "##### Getting Session Key #####\r\n";
		GetSecretKeyLite();
		break;
}
?>
