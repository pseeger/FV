<?php
define('PX_VER_PARSER', '22131');
define('PX_DATE_PARSER', '2011-04-11');
define('PARSER_MAX_SPEED', 8);
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
$use_proxy = file_exists('proxy.txt') && count(file('proxy.txt'));
class Curlfetcher {
	private $ch;
	private $lasturl;

	public function __construct() {
		$this->ch = curl_init();
		global $use_proxy;
		global $proxy_settings;
		curl_setopt_array($this->ch, array(CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10', CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_AUTOREFERER => true, CURLOPT_HEADER => false, CURLOPT_ENCODING => 'gzip'));
		$proxy_settings = $GLOBALS['proxy_settings'];
		if ($GLOBALS['use_proxy']) curl_setopt($this->ch, CURLOPT_PROXY, $proxy_settings[0] . ':' . $proxy_settings[1]);
		if (count($proxy_settings) > 2) curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $proxy_settings[2] . ':' . $proxy_settings[3]);
	}

	public function get($url) {
		if ($url != $this->lasturl) {
			curl_setopt($this->ch, CURLOPT_URL, $url);
			$this->lasturl = $url;
		}
		$res = curl_exec($this->ch);
		if (curl_getinfo($this->ch, CURLINFO_HTTP_CODE) >= 400) {
			AddLog2(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) . "\r\n");
			return 0;
		}
		if (curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE) == 'application/x-gzip') return gzuncompress($res);
		else return $res;
	}

	public function post($url, $postdata = '', $contentType = '') {
		curl_setopt_array($this->ch, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postdata, CURLOPT_HTTPHEADER => array('Content-Length: ' . strlen($postdata), 'Content-Type: ' . $contentType)));
		if ($url != $this->lasturl) {
			curl_setopt($this->ch, CURLOPT_URL, $url);
			$this->lasturl = $url;
		}
		$res = curl_exec($this->ch);
		//Reset the state
		curl_setopt_array($this->ch, array(CURLOPT_POSTFIELDS => '', CURLOPT_HTTPHEADER => array()));
		if (curl_getinfo($this->ch, CURLINFO_HTTP_CODE) >= 400) {
			AddLog2(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) . "\r\n");
			return 0;
		}
		return $res;
	}

	public function setCookieJar($location) { curl_setopt($this->ch, CURLOPT_COOKIEFILE, $location); }
}

if (function_exists('curl_exec')) $GLOBALS['curlfetcher'] = new Curlfetcher();
// Set to 1 if you want to load plugins like pre v1.10, good for debuging plugin code
// Otherwise keep 0 and we wont load plugins when they arent needed
$plugin_developer = 0;
$is_debug = false;
error_reporting(E_ALL);
ini_set('display_errors', true);
$tmp = pack("d", 1); // determine the multi-byte ordering of this machine temporarily pack 1
define("AMFPHP_BIG_ENDIAN", $tmp == "\0\0\0\0\0\0\360\77");
$GLOBALS['amfphp']['encoding'] = 'amf3';
$GLOBALS['amfphp']['native'] = 0; //TODO: Investigate
$GLOBALS['amfphp']['adapterMappings'] = array();
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
	if (strlen($timezone) > 2) date_default_timezone_set($timezone);
}
else @date_default_timezone_set('America/Los_Angeles');
if ($is_debug) @error_log(print_r($GLOBALS['argv'], true));
# connect
$vDataDB = Parser_SQlite_Connect(PARSER_SQLITE);
$Load_Farm_Read_Size = 0; //0 = Read all, any other number sets the size read
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
	if ($request) $amf->_bodys[0]->_value[1][0]['params'][0] = $request;
	if ($function) $amf->_bodys[0]->_value[1][0]['functionName'] = $function;
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
	touch($userId . '_need_restart.txt'); //creating this file will cause the game to restart
	die;
}

// ------------------------------------------------------------------------------
// GetAMFHeaders
//  @return array auth parameters
// ------------------------------------------------------------------------------
function GetAMFHeaders() {
	global $userId, $flashRevision, $token;
	LoadAuthParams();
	return array('sigTime' => time() . '.0000', 'token' => $token, 'flashRevision' => $flashRevision, 'masterId' => $userId, 'wid' => 0, 'snId' => 1, 'uid'=>$userId);
}

function LoadAuthParams() {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
}

function SaveAuthParams() {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	file_put_contents(F('params.txt'), implode(';', array($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy)));
}

function GetSequense() {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	LoadAuthParams();
	$sequence++;
	SaveAuthParams();
	return $sequence;
}

function SetSequense($new_sequence) {
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy;
	LoadAuthParams();
	$sequence = $new_sequence;
	SaveAuthParams();
}

function save_botarray($array, $filename) { file_put_contents($filename, serialize($array)); }

function load_botarray($filename) { return unserialize(file_get_contents($filename)); }

function Connect($server = '') { return -1; }

// ------------------------------------------------------------------------------
// EchoData returns data in the main application
//  @param string $data data
// ------------------------------------------------------------------------------
function EchoData($data) {
	echo $data;
	file_put_contents('out.txt', $data);
}

// ------------------------------------------------------------------------------
// proxy_GET can use a proxy to get the gameSettings/description xml files
// ------------------------------------------------------------------------------
function proxy_GET($url) { return $GLOBALS['curlfetcher']->get($url); }

// ------------------------------------------------------------------------------
// proxy_GET_FB can use a proxy to get FB-Sites
// ------------------------------------------------------------------------------
function proxy_GET_FB($url, $vPostGet = 'GET', $vPostData = '') {
	$GLOBALS['curlfetcher']->setCookieJar(F('cookies.txt'));
	if ($vPostGet == 'POST') return $GLOBALS['curlfetcher']->post($url, $vPostData, 'application/x-www-form-urlencoded');
	else return $GLOBALS['curlfetcher']->get($url);
}

// ------------------------------------------------------------------------------
// Request sends AMF request to the farmville server
//  @param resourse $s socket connection
//  @param string $result AMF-serialized request
//  @return string http answer
// ------------------------------------------------------------------------------
function Request($s, $data) { return $GLOBALS['curlfetcher']->post(farmer_url, $data, 'application/x-amf'); }

// ------------------------------------------------------------------------------
// GetFarmserver returns farmville server name
//  @return string Server name
// ------------------------------------------------------------------------------
function GetFarmserver() {
	@list($res, $res2) = @explode(';', trim(@file_get_contents('farmserver.txt')));
	if (empty($res)) {
		$flashVars = parse_flashvars();
		$app_url = (@$flashVars['app_url']) ? $flashVars['app_url'] : 'http://fb-ak-0.farmville.com/';
		preg_match('/http:\/\/(.*?)\//', $app_url, $match);
		$res = $match[1];
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
		$app_url = (@$flashVars['app_url']) ? $flashVars['app_url'] : 'http://fb-ak-0.farmville.com/';
		$res = $app_url . 'flashservices/gateway.php';
	}
	return $res;
}

// ------------------------------------------------------------------------------
// DoInit Loading farms
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function DoInit($vDoCheck = '') {
	global $vWorldtype;
	AddLog2("Init user. Load Farm");
	$T = time(true);
	Hook('before_load_farm');
	global $userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy, $servertime;
	LoadAuthParams();
	SetSequense(0);
	// Create Init request
	$amf = CreateRequestAMF('', 'UserService.initUser');
	$amf->_bodys[0]->_value[1][0]['params'][0] = '';
	$amf->_bodys[0]->_value[1][0]['params'][1] = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2] = true;
	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);
	if ($res == 'OK') {
		$vWorldtype = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['currentWorldType'];
		if ($vDoCheck == 'check_farm') {
			$vSettingsWorldtype = @file_get_contents(F('worldtype.txt'));
			if (strlen($vSettingsWorldtype) == 0) $vSettingsWorldtype = 'farm';
			$vSettingsWorldfreez = @file_get_contents(F('worldfreez.txt'));
			if (strlen($vSettingsWorldfreez) == 0) $vSettingsWorldfreez = 'freez';
			if ($vSettingsWorldtype <> $vWorldtype) {
				$amf = CreateRequestAMF('', 'UserService.saveOptions');
				$amf->_bodys[0]->_value[1][0]['params'][0]['shouldFreezeFarm'] = $vSettingsWorldfreez == 'freez'; # false continue farm / true freez farm
				$amf->_bodys[0]->_value[1][0]['params'][0]['sfxDisabled'] = true;
				$amf->_bodys[0]->_value[1][0]['params'][0]['musicDisabled'] = true;
				$amf->_bodys[0]->_value[1][0]['params'][0]['graphicsLowQuality'] = true;
				$amf2 = RequestAMFIntern($amf);
				$res = CheckAMF2Response($amf2);
				if ($res == 'OK') AddLog2("DoInit: FreezFarm successful changed to $vSettingsWorldfreez");
				else AddLog2("DoInit: FreezFarm error $res changing to $vSettingsWorldfreez");
				$amf = CreateRequestAMF('', 'WorldService.loadOwnWorld');
				$amf->_bodys[0]->_value[1][0]['params'][0] = $vSettingsWorldtype;
				$amf->_bodys[0]->_value[1][0]['params'][1] = true;
				$amf->_bodys[0]->_value[1][0]['params'][2] = $vSettingsWorldfreez == 'freez'; # false continue farm / true freez farm
				$amf2 = RequestAMFIntern($amf);
				$res = CheckAMF2Response($amf2);
				if ($res == 'OK') AddLog2("DoInit: SwitchFarm successful switched to $vSettingsWorldtype");
				else AddLog2("DoInit: SwitchFarm error $res switching to $vSettingsWorldtype");
				DoInit('check_farm');
				return '';
			}
		}
		LoadAuthParams();
		// get flashSessionKey
		$sequence = 1;
		if (isset($amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['flashSessionKey'])) $flashSessionKey = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['flashSessionKey'];
		$servertime = $amf2->_bodys[0]->_value['data'][0]['serverTime'];
		// save to file $flashSessionKey, $xp, $energy
		$xp = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['xp'];
		$energy = $amf2->_bodys[0]->_value['data'][0]['data']['energy'];
		SaveAuthParams();
		// get extra info
		foreach (array('level', 'gold', 'cash') as $var) $$var = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player'][$var];
		if ($vWorldtype == 'england') {
			$sizeX = $amf2->_bodys[0]->_value['data'][0]['data']['world']['sizeX'];
			$sizeY = $amf2->_bodys[0]->_value['data'][0]['data']['world']['sizeY'];
		}
		else {
			$sizeX = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['sizeX'];
			$sizeY = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['sizeY'];
		}
		$firstname = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['attr']['name'];
		$locale = $amf2->_bodys[0]->_value['data'][0]['data']['locale'];
		// save to file $level, $coins, $cash, $sizex, $sizey
		file_put_contents(F('playerinfo.txt'), implode(';', array(0, 0, 0, $sizeX, $sizeY, $firstname, $locale)));
		// save world to file
		save_botarray($amf2->_bodys[0]->_value, F('world.txt'));
		// get objects on farm
		if ($vWorldtype == 'england') $objects = $amf2->_bodys[0]->_value['data'][0]['data']['world']['objectsArray'];
		else $objects = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['world']['objectsArray'];
		// FarmFIX/object split
		$my_farm_is_fucked_up = 0;
		if (file_exists('farmfix.txt')) $my_farm_is_fucked_up = trim(file_get_contents('farmfix.txt'));
		if ($my_farm_is_fucked_up == 1) {
			$obj_total = count($objects);
			if ($obj_total <= 800) AddLog2("You should turn off Object Split if your farm works without it.");
			if ($obj_total > 1) {
				// 1ST FILE
				//TODO: replace this with array_chunk
				for ($i = 0; $i <= round($obj_total / 2); $i++) $obj_split_one[] = $objects[$i];
				save_botarray($obj_split_one, F('objects_1.txt'));
				// end 1ST FILE
				// 2nd FILE
				for ($i = round($obj_total / 2) + 1; $i < $obj_total; $i++) $obj_split_two[] = $objects[$i];
				save_botarray($obj_split_two, F('objects_2.txt'));
				// end 2nd FILE
			}
		} //FarmFIX
		else save_botarray($objects, F('objects.txt'));
		//TODO: Replace with some loops
		// save collection counters to a file
		$c_count = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['collectionCounters'];
		save_botarray($c_count, F('ccount.txt'));
		// save rewards to a file
		$rewardlinks = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['friendRewards'];
		save_botarray($rewardlinks, F('rlinks.txt'));
		// save lonelyanimals to a file
		$animallinks = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['lonelyAnimals'];
		save_botarray($animallinks, F('ralinks.txt'));
		// save giftbox info for plugins
		$storagedata = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData'];
		save_botarray($storagedata, F('storagedata.txt'));
		$ingiftbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-1'];
		foreach ($ingiftbox as $key => $item) $ingiftbox[$key] = isset($item[0]) ? $item[0] : 0;
		save_botarray($ingiftbox, F('ingiftbox.txt'));
		// save consumable info for plugins
		$inconbox = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-6'];
		foreach ($inconbox as $key => $item) $inconbox[$key] = isset($item[0]) ? $item[0] : 0;
		save_botarray($inconbox, F('inconbox.txt'));
		// save storage info for plugins
		if ($vWorldtype == 'england') $instorage = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-8'];
		else $instorage = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['storageData']['-2'];
		foreach ($instorage as $key => $item) $instorage[$key] = $item[0];
		save_botarray($instorage, F('instorage.txt'));
		// save neighbors list
		$neighbors = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighbors'];
		save_botarray($neighbors, F('neighbors.txt'));
		$pneighbors = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['pendingNeighbors'];
		save_botarray($pneighbors, F('pneighbors.txt'));
		$nAQ = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighborActionQueue']['m_actionQueue'];
		save_botarray($nAQ, F('nactionqueue.txt'));
		$nAL = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['neighborActionLimits']['m_neighborActionLimits'][date('ymd', $servertime)];
		save_botarray($nAL, F('nactionlimit.txt'));
		// save crop mastery list
		$px_cropmastery = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['masteryCounters'];
		save_botarray($px_cropmastery, F('cropmastery.txt'));
		// save crop mastery count
		$cropmasterycount = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['mastery'];
		save_botarray($cropmasterycount, F('cropmasterycount.txt'));
		// save feature credits
		$featurecredits = $amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['featureCredits'][$vWorldtype];
		save_botarray($featurecredits, F('featurecredits.txt'));
		// save ribbon data
		$px_achievements = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['achCounters'];
		$earned_ribbons = @$amf2->_bodys[0]->_value['data'][0]['data']['userInfo']['player']['achievements'];
		$ribbon_merge = array();
		foreach ($px_achievements as $name => $data) $ribbon_merge[$name]['count'] = $data;
		if (@count($earned_ribbons) > 0) foreach ($earned_ribbons as $name => $data) $ribbon_merge[$name]['earned'] = $data;
		save_botarray($ribbon_merge, F('ach_count.txt'));
		// save_botarray ($array, $filename);
	}
	Hook('after_load_farm');
	AddLog2("result $res");
	AddLog2('Init Time: ' . (time(true) - $T) . 'Seconds, worldtype ' . $vWorldtype);
	return $res;
}

// ------------------------------------------------------------------------------
// Arbeit Perform work on the farm
// ------------------------------------------------------------------------------
function Arbeit() {
	global $settings, $need_reload, $px_Setopts;
	global $userId, $flashRevision, $botlitever, $vWorldtype, $res_str, $plugin_developer;
	$argv = @$GLOBALS['argv'];
	if (strlen($userId) == 0 && strlen(@$argv[2]) > 0) $userId = @$argv[2];
	$flashRevision = @$argv[3];
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
	if (is_file(LogF("log2.txt"))) {
		$vDir = './farmville-logs';
		if (!is_dir($vDir)) @mkdir($vDir);
		copy(LogF("log2.txt"), './farmville-logs/' . ($vWorldtype == 'england' ? 'xuk' : '') . $userId . '_log2_' . date('Ymd_His') . '.log');
	}
	#// clear advanced log
	fclose(fopen(LogF('log2.txt'), "w+"));
	while (file_exists('notrun_parser.txt') || file_exists('notrun_parser_' . $userId . '.txt')) {
		AddLog2("Bot Paused. Next check in 30 seconds.");
		sleep(30);
	}
	parse_neighbors();
	AddLog2('start');
	Hook('before_work');
	// Init
	if (DoInit('check_farm') != 'OK') RaiseError(2);
	else $res_str = ''; //for main logs
	Hook('before_load_settings');
	// load settings
	if (!function_exists('LoadSavedSettings')) die("\n\nSettings plugin installed incorrectly no LoadSavedSettings found!\n\n");
	$px_Setopts = LoadSavedSettings();
	$enable_acceptgifts_num = @$px_Setopts['acceptgifts_num'];
	if (strlen((@$px_Setopts['acceptgifts_num'])) == 0) $enable_acceptgifts_num = 10;
	Hook('after_load_settings');
	if ((@$px_Setopts['sendgifts'])) Parser_SendGift();
	//TODO: make a seperate function
	if ((@$px_Setopts['acceptgifts'])) {
		$vGiftReqs = Parser_ReadReq();
		save_botarray($vGiftReqs, F('gift_reqs.txt'));
		AddLog2('Parser_gift_reqs: ' . count($vGiftReqs) . ' to accept');
		if (is_array($vGiftReqs) && count($vGiftReqs) > 0) {
			if ((@$px_Setopts['acceptgifts_twice'])) $vGiftReqs = array_merge($vGiftReqs, $vGiftReqs);
			$vGCount = 0;
			foreach ($vGiftReqs as $vI => $vData) {
				if ($vGCount >= $enable_acceptgifts_num) break;
				$vGCount++;
				$vWhat = explode('&', str_replace(array('?', '='), array('&', '&'), $vData['action_url']));
				AddLog2('Parser_gift_reqs: ' . $vGCount . ' accept ' . Units_GetRealnameByName($vWhat[4]) . ' (' . $vWhat[4] . ') from ' . GetNeighborRealName($vWhat[2]) . ' (' . $vWhat[2] . ')');
				error_log('"' . GetNeighborRealName($vWhat[2]) . '";"' . $vWhat[2] . '";"' . $vWhat[4] . '";"' . date('Y.m.d H:i:s') . '"' . "\n", 3, LogF('gifts_accepted.csv'));
				$vResponse = proxy_GET_FB("http://www.facebook.com/ajax/reqs.php?__a=1", 'POST', $vData['post_data']);
				$vResponse = proxy_GET_FB($vData['action_url']);
				if (!empty($vResponse)) AddLog2('Parser_gift_reqs: ' . $vGCount . ' accept - Success');
				else AddLog2('Parser_gift_reqs: ' . $vGCount . ' accept - Failed');
				if ((@$px_Setopts['acceptgifts_sendback'])) {
					preg_match_all('/<form.*?action="(.*?)".*?<\/form>/ims', $vResponse, $vTYForms);
					foreach ($vTYForms[0] as $vJ => $vTYForm) {
						if (stripos($vTYForm, 'thank you') !== false || stripos($vTYForm, 'send to') !== false) {
							AddLog2('Parser_gift_reqs: send thankyou-gift ' . Units_GetRealnameByName($vWhat[4]) . ' (' . $vWhat[4] . ') to ' . GetNeighborRealName($vWhat[2]) . ' (' . $vWhat[2] . ')');
							error_log('"' . GetNeighborRealName($vWhat[2]) . '";"' . $vWhat[2] . '";"' . $vWhat[4] . '";"' . date('Y.m.d H:i:s') . '"' . "\n", 3, LogF('gifts_send_thankyou.csv'));
							preg_match_all('/.*action="([^"]*)".*/ims', $vTYForm, $vAction);
							preg_match_all('/.*giftRecipient=([^&]*).*type="([^"]*)".*content="([^"]*)".*id="([^"]*)".*post_form_id=([^&]*).*/ims', $vTYForm, $vTYFields);
							$vPostData = 'app_id=102452128776&to_ids[0]=' . $vTYFields[1][0] . '&request_type=' . urlencode($vTYFields[2][0]) . '&invite=false&content=' . urlencode(html_entity_decode($vTYFields[3][0])) . '&preview=true&is_multi=false&is_in_canvas=true&form_id=' . $vTYFields[4][0] . '&prefill=true&message=&donot_send=false&include_ci=false&__d=1&post_form_id=' . $vTYFields[5][0] . '&fb_dtsg=' . $vData['fb_dtsg'] . '&lsd&post_form_id_source=AsyncRequest';
							$vResponse2 = proxy_GET_FB("http://www.facebook.com/fbml/ajax/prompt_send.php?__a=1", 'POST', $vPostData);
							$vPostData = str_replace('&preview=true&', '&preview=false&', $vPostData);
							$vResponse3 = proxy_GET_FB("http://www.facebook.com/fbml/ajax/prompt_send.php?__a=1", 'POST', $vPostData);
							if (stripos(strip_tags($vResponse3), '"errorSummary"')) AddLog2('Parser_gift_reqs: send thankyou-gift - Failed');
							else AddLog2('Parser_gift_reqs: send thankyou-gift - Success');
							$vResponse4 = proxy_GET_FB(html_entity_decode($vAction[1][0]), 'POST', '');
							unset($vResponse2, $vResponse3, $vResponse4);
						}
					}
				}
				$need_reload = true;
				unset($vResponse);
			}
		}
	}
	if ($need_reload) DoInit();
	$need_reload = false;
	if ((@$px_Setopts['lonlyanimals'])) {
		AddLog2("check lonlyanimal");
		Do_Check_Lonlyanimals();
	}
	if ((@$px_Setopts['wanderinganimals'])) {
		AddLog2("check wanderinganimals");
		Do_Check_Wanderinganimals();
	}
	if ((@$px_Setopts['acceptneighborhelp'])) {
		AddLog2("accept neighbor help");
		Do_Accept_Neighbor_Help();
	}
	if ((@$px_Setopts['e_biplane'])) {
		AddLog2("biplane instantgrow");
		$plot_list = GetObjects('Plot'); //get plots
		$cntplots = 0;
		foreach ($plot_list as $plot) {
			if (($plot['state'] == 'planted')) $cntplots++;
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
	if ((@$px_Setopts['e_harvest'])) {
		AddLog2('harvest crops');
		$plot_list = GetObjects('Plot'); //get plots
		$plots = array();
		switch ($px_Setopts['e_harvest_spec']) {
			//   harvest specific only
			case 1:
				AddLog2('n0m mod: Harvest only ' . $px_Setopts['spec_crop_quantity'] . ' of ' . $px_Setopts['spec_crop'] . ' crops!');
				$iPlotCount = 0;
				foreach ($plot_list as $plot) {
					if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') && $plot['itemName'] == $px_Setopts['spec_crop']) {
						if (($px_Setopts['spec_crop_quantity'] == 0) || ($iPlotCount < $px_Setopts['spec_crop_quantity'])) {
							$plots[] = $plot;
							$iPlotCount++;
						}
					}
				}
				AddLog2('Ready plots: ' . count($plots));
				break;
			//   harvest all
			default:
				foreach ($plot_list as $plot) if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe')) $plots[] = $plot;
				AddLog2('n0m mod: Harvest ALL ( ' . count($plots) . ' ) crops!');
				break;
		}
		unset($plot_list);
		if (count($plots) > 0) Do_Farm_Work_Plots($plots, 'harvest'); //harvest land
		unset($plots);
	}
	Hook('after_harvest');
	Hook('before_harvest_buildings');
	if (@$px_Setopts['e_h_building'] == 1) {
		AddLog2("harvest buildings");
		$building_list = array();
		$buildingassoc = array('dairy' => 'DairyFarmBuilding', 'coop' => 'ChickenCoopBuilding', 'horse' => 'HorseStableBuilding', 'nursery' => 'NurseryBuilding', 'bees' => 'BeehiveBuilding', 'pigs' => 'PigpenBuilding', 'hauntedhouse' => 'HalloweenHauntedHouseBuilding', 'trough' => 'FeedTroughBuilding', 'orchard' => 'OrchardBuilding', 'turkeyroost' => 'TurkeyRoostBuilding');
		$featureBuildings = array('wworkshop','snowman', 'duckpond', 'ccastle', 'ccottage', 'sgarden');
		foreach ($buildingassoc as $setting => $objectname) if (@$px_Setopts['e_h_' . $setting] == 1) {
			$x = GetObjects($objectname);
			//Check whether $building_list+=$x would work
			if (is_array($x)) $building_list = array_merge($building_list, $x);
		}
		foreach($featureBuildings as $fBuilding) if(@$px_Setopts['e_h_building'.$fBuilding]) {
			$x = GetObjects("FeatureBuilding");
			if (is_array($x)) $building_list = array_merge($building_list, $x);
			break;
		}
		if (count($building_list) > 0) {
			$buildings = array();
			foreach ($building_list as $plot) if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') || (@$plot['m_hasAnimal'] == 1)) $buildings[] = $plot;
			if (count($buildings) > 0) Do_Farm_Work($buildings); //harvest buildings
			$buildings = array();
			foreach ($building_list as $plot) {
				if ($plot['className'] == 'HalloweenHauntedHouseBuilding') {
					list($vUSec, $vSec) = explode(" ", microtime());
					$vPlantTime = (string)$vSec . substr((string)$vUSec, 2, 3);
					if ($plot['plantTime'] < ($vPlantTime - 82800000)) $buildings[] = $plot;
				}
			}
			foreach ($building_list as $plot) {
				if ($plot['className'] == 'FeatureBuilding') {
					if ((@$px_Setopts['e_h_building_wworkshop'] == 1 && $plot['itemName'] == 'winterworkshop_finished') || (@$px_Setopts['e_h_building_snowman'] == 1 && $plot['itemName'] == 'snowman2010_finished') || (@$px_Setopts['e_h_building_duckpond'] == 1 && $plot['itemName'] == 'duckpond_finished') || (@$px_Setopts['e_h_building_ccastle'] == 1 && $plot['itemName'] == 'valentines2011_finished') || (@$px_Setopts['e_h_building_lcottage'] == 1 && $plot['itemName'] == 'stpatty2011_finished' || ($enable_harvest_building_sgarden == 1 && $plot['itemName']=='springgardenbuildable2011_finished'))) {
						list($vUSec, $vSec) = explode(" ", microtime());
						$vPlantTime = (string)$vSec . substr((string)$vUSec, 2, 3);
						if ($plot['plantTime'] < ($vPlantTime - 82800000)) $buildings[] = $plot;
					}
				}
			}
			foreach($buildings as $b) Do_Farm_Work(array($b)); //harvest buildings one by one
			unset($building_list);
		}
	}
	Hook('after_harvest_buildings'); //after building harvest
	Hook('before_harvest_animals'); //get product from livestock
	if ((@$px_Setopts['e_h_animal'])) {
		AddLog2("harvest animals");
		$animals = GetObjects('Animal'); //get list of animals
		$Transforms = unserialize(file_get_contents('transforms.txt')); //get transforms
		$harvest_animals = array();
		$transform_animals = array();
		foreach ($animals as $animal) {
			if (($animal['state'] != "grown") && ($animal['state'] != "ripe")) continue;
			$px_animal_check = $animal['itemName'];
			if (isset($px_Setopts['H_Animal'])) foreach ($px_Setopts['H_Animal'] as $px_a_name => $px_h_check) {
				if ((stristr($px_animal_check, $px_a_name) !== false) && ($px_h_check != 1)) {
					// skip this animal
					continue 2;
				}
			}
			else {
				AddLog2("H_Animal not set.. skipping animal harvest");
				break;
			}
			// H_Animal should always be set
			// if we transformed animals on accident ppl would probably be mad lol
			if (in_array($animal['itemName'], $Transforms)) $transform_animals[] = $animal;
			else $harvest_animals[] = $animal;
		}
		if ((@$px_Setopts['e_h_farmhands'])) {
			$vRatio = round((count($transform_animals) + count($harvest_animals)) * 100 / count($animals));
			if ($vRatio >= @$px_Setopts['e_h_farmhands_at']) {
				$inconbox = @unserialize(file_get_contents(F('inconbox.txt')));
				if ((!isset($inconbox['AA'])) || $inconbox['AA'] == 0) AddLog2("farmhands: you dont have farmhands");
				elseif ($inconbox['AA'] <= @$px_Setopts['e_h_farmhands_min']) AddLog2("farmhands: you dont have enough farmhands (" . $inconbox['AA'] . ")");
				else {
					AddLog2("farmhands: harvest now, " . $vRatio . "% ready (" . $inconbox['AA'] . " farmhands remaining)");
					$need_reload = Do_Farmhands_Arborists('farmhands');
				}
			}
			else AddLog2("farmhands: now " . $vRatio . "% ready, harvest later at " . @$px_Setopts['e_h_farmhands_at'] . "%");
		}
		else {
			if (count($transform_animals) > 0) Do_Farm_Work($transform_animals, "transform");
			if (count($harvest_animals) > 0) Do_Farm_Work($harvest_animals);
		}
		unset($transform_animals, $harvest_animals);
	}
	Hook('after_harvest_animals');
	Hook('before_harvest_tree');
	// harvest from trees
	if ((@$px_Setopts['e_h_tree'])) {
		AddLog2("harvest trees");
		$trees = array();
		$plot_list = GetObjects('Tree'); //get list of trees
		foreach ($plot_list as $plot) if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe')) $trees[] = $plot;
		if ((@$px_Setopts['e_h_arborist'])) {
			$vRatio = round(count($trees) * 100 / count($plot_list));
			if ($vRatio >= @$px_Setopts['e_h_arborist_at']) {
				$inconbox = @unserialize(file_get_contents(F('inconbox.txt')));
				if ((!isset($inconbox['A9'])) || $inconbox['A9'] == 0) AddLog2("arborists: you dont have arborists");
				elseif ($inconbox['A9'] <= @$px_Setopts['e_h_arborist_min']) AddLog2("farmhands: you dont have enough arborists (" . $inconbox['A9'] . ")");
				else {
					AddLog2("arborists: harvest now, " . $vRatio . "% ready (" . $inconbox['A9'] . " arborists remaining)");
					$need_reload = Do_Farmhands_Arborists('arborists');
				}
			}
			else AddLog2("arborists: now " . $vRatio . "% ready, harvest later at " . @$px_Setopts['e_h_arborist_at'] . "%");
		}
		elseif (count($trees) > 0) Do_Farm_Work($trees); //harvest trees
		unset($trees, $plot_list);
	}
	Hook('after_harvest_tree');
	Hook('before_hoe');
	if ((@$px_Setopts['e_hoe'])) { // we've selected to auto-plow plots
		AddLog2("plowing plots");
		if ($need_reload) {
			$res = DoInit(); //reload farm
			$need_reload = false;
		}
		$plots = array();
		$plot_list = GetObjects('Plot');
		foreach ($plot_list as $plot) if (($plot['state'] == 'withered') || ($plot['state'] == 'fallow')) $plots[] = $plot;
		unset($plot_list);
		AddLog2('Preparing to plow ' . count($plots) . ' plots');
		if (count($plots) > 0) Do_Farm_Work_Plots($plots, 'plow'); //plow land
		AddLog2('Plowed Plots');
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
	if ((@$px_Setopts['e_seed'] || @$px_Setopts['e_combine']) && (file_exists(F('seed.txt')))) { // fix infinite loop when no file exists
		// get list of plants
		$seed_list = explode(';', trim(file_get_contents(F('seed.txt'))));
		// We need to move Default seed to the end of the array
		// and normalize entries
		foreach ($seed_list as $one_seed_string) {
			$one_seed_array = @explode(':', $one_seed_string);
			if ($one_seed_array[1] == 'Default') $seed_default = $one_seed_array[0];
			else {
				if(!empty($seed_list_new)) if ($last_seed == $one_seed_array[0]) {
					$last_seed_string = array_pop($seed_list_new);
					$last_seed_array = @explode(':', $last_seed_string);
					$seed_list_new[] = $one_seed_array[0] . ':' . ($one_seed_array[1] + $last_seed_array[1]);
				}
				else $seed_list_new[] = $one_seed_string;
				$last_seed = $one_seed_array[0];
			}
		}
		if (isset($seed_default)) $seed_list_new[] = $seed_default . ':Default';
		$seed_list = $seed_list_new;
		unset($seed_list_new);
		$plots = GetObjects('Plot');
		// Find empty plots
		$plowed_plots = array();
		foreach ($plots as $plot) {
			if ($plot['state'] == 'plowed') $plowed_plots[] = $plot;
			if (@$px_Setopts['e_combine'] && (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') || ($plot['state'] == 'withered') || ($plot['state'] == 'fallow'))) $plowed_plots[] = $plot;
		}
		$seed_plots = array();
		$append_seed_array = array();
		foreach ($plowed_plots as $plowed_key => $plot) {
			foreach ($seed_list as $seed_key => $itemName) {
				$px_itemName = explode(':', $itemName);
				if (empty($px_itemName[0])) break 2; //seedlist is empty
				$plot['itemName'] = $px_itemName[0];
				$seed_plots[] = $plot;
				if ($px_itemName[1] != "Default") {
					$px_itemName[1]--;
					if ($px_itemName[1] == 0) unset($seed_list[$seed_key]);
					else $seed_list[$seed_key] = "$px_itemName[0]:$px_itemName[1]";
					if ((@$px_Setopts['e_seed_keep'])) $append_seed_array[$px_itemName[0]]++;
				}
				break;
			} //seedlist
		} //plotlist
		// save list
		if ((@$px_Setopts['e_seed_keep'])) foreach ($append_seed_array as $append_seed => $append_count) {
			$seed_list[] = $append_seed . ':' . $append_count;
		}
		file_put_contents(F('seed.txt'), implode(';', $seed_list));
		if (count($seed_plots) > 0) {
			if ((@$px_Setopts['e_combine'])) Do_Farm_Work_Plots($seed_plots, 'combine'); //combine
			if ((@$px_Setopts['e_seed'])) Do_Farm_Work_Plots($seed_plots, 'place'); //plant crops
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
	$px_Setopts['current_farm'] = file_get_contents(F('worldtype.txt'));
	if (@file_get_contents(F('worldswitch.txt')) == 'on') {
		$vWorldtype = $vWorldtype <> 'farm' ? 'farm' : 'england';
		AddLog2("Parser: switch worldtype to $vWorldtype");
		file_put_contents(F('worldtype.txt'), $vWorldtype);
	}
	AddLog2("memory_peak_usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB");
	AddLog2("finish");
}

function Parser_Get_Locale() {
	global $flashRevision;
	global $vDataDB;
	pruneDir('./farmville-flash');

	$flashVars = parse_flashvars();
	$vLocalFile = 'farmville-flash/' . $flashRevision . '_flashLocaleA.swf';
	parser_download($vLocalFile, $flashVars['localization_url']);
	$vFlashlocalDumpFile = $vLocalFile . '.txt.';
	if (!file_exists($vFlashlocalDumpFile)) {
		$vFlashDump = shell_exec('swfdump.exe -a ' . $vLocalFile . ' 2>&1 ');
		file_put_contents($vFlashlocalDumpFile, $vFlashDump);
		unset($vFlashDump);
	}
	$vLocalFile = 'farmville-flash/' . $flashRevision . '_assethash.swf';
	parser_download($vLocalFile, $flashVars['assethash_url']);
	$vAssethashDumpFile = $vLocalFile . '.txt.';
	if (!file_exists($vAssethashDumpFile)) {
		$vFlashDump = shell_exec('swfdump.exe -a ' . $vLocalFile . ' 2>&1 ');
		file_put_contents($vAssethashDumpFile, $vFlashDump);
		unset($vFlashDump);
	}
	$vLocalFile = 'farmville-flash/' . $flashRevision . '_FarmGame.swf';
	parser_download($vLocalFile, $flashVars['swfLocation']);
	$vGameDumpFile = $vLocalFile . '.txt.';
	if (!file_exists($vGameDumpFile)) {
		$vFlashDump = shell_exec('swfdump.exe -a ' . $vLocalFile . ' 2>&1 ');
		$vFlashDump = substr($vFlashDump, strpos($vFlashDump, "TSeenRevampEmail=TSeenRevampEmail"));
		$vLastPos = strpos($vFlashDump, 'Display::AssetHashMap');
		if ($vLastPos > 1000) $vFlashDump = substr($vFlashDump, 0, $vLastPos);
		file_put_contents($vGameDumpFile, $vFlashDump);
		unset($vFlashDump);
	}
	$vFlashDump = file_get_contents($vFlashlocalDumpFile);
	$vDataDB->queryExec('BEGIN TRANSACTION');
	$vResult = $vDataDB->query("select * from units where field='name' and name not in (select name from units where field='realname' and name<>content)");
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vHashImage = '';
		$vUrlPosition = strpos($vFlashDump, 'pushstring "' . $vImage . '_friendlyName');
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false) {
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false) {
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}
		if (strlen($vHashImage) > 0 && strpos($vHashImage, '\\') === false) $vDataDB->query('insert into units("name","field","content") values("' . $vName . '", "realname" , "' . $vHashImage . '")');
		else $vDataDB->query('insert into units("name","field","content") values("' . $vName . '","realname","' . $vImage . '")');
	}
	$vDataDB->queryExec('COMMIT TRANSACTION');
	$vDataDB->queryExec('BEGIN TRANSACTION');
	$vResult = $vDataDB->query("select * from collectables where field='name' and name not in (select name from collectables where field='realname' and name<>content)");
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vUrlPosition = strpos($vFlashDump, 'pushstring "' . $vImage . '_friendlyName');
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false) {
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false) {
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}
		if (strlen($vHashImage) > 0 && strpos($vHashImage, '\\') === false) $vDataDB->query('insert into collectables("name","field","content") values("' . $vName . '", "realname" , "' . $vHashImage . '")');
		else $vDataDB->query('insert into collectables("name","field","content") values("' . $vName . '","realname","' . $vImage . '")');
	}
	$vDataDB->queryExec('COMMIT TRANSACTION');
	$vDataDB->queryExec('BEGIN TRANSACTION');
	$vResult = $vDataDB->query("select * from achievements where field='name' and name not in (select name from achievements where field='realname' and name<>content)");
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vUrlPosition = strpos($vFlashDump, 'pushstring "' . $vImage . '_friendlyName');
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false) {
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false) {
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}
		if (strlen($vHashImage) > 0 && strpos($vHashImage, '\\') === false) $vDataDB->query('insert into achievements("name","field","content") values("' . $vName . '", "realname" , "' . $vHashImage . '")');
		else $vDataDB->query('insert into achievements("name","field","content") values("' . $vName . '","realname","' . $vImage . '")');
	}
	$vDataDB->queryExec('COMMIT TRANSACTION');
	$vDataDB->queryExec('BEGIN TRANSACTION');
	$vResult = $vDataDB->query("select * from quests where field='title' and name not in (select name from quests where field='realname' and name<>content)");
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		$vFName = str_replace('_Title', '', $vImage);
		$vUrlPosition = strpos($vFlashDump, 'pushstring "' . $vImage);
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_description') : $vUrlPosition;
		$vUrlPosition = $vUrlPosition === false ? strpos($vFlashDump, 'pushstring "' . $vImage . '_Title') : $vUrlPosition;
		if ($vUrlPosition !== false) {
			$vUrlPosition += 12;
			$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
			$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
			if ($vUrlPosition2 !== false) {
				$tmpLength = (strpos($vFlashDump, '"', ($vUrlPosition2 + 12))) - ($vUrlPosition2 + 12);
				$vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $tmpLength);
			}
		}
		if (strlen($vHashImage) > 0 && strpos($vHashImage, '\\') === false) $vDataDB->query('insert into quests("name","field","content") values("' . $vImage . '", "realname" , "' . $vHashImage . '")');
		else $vDataDB->query('insert into quests("name","field","content") values("' . $vImage . '","realname","' . $vImage . '")');
	}
	$vDataDB->queryExec('COMMIT TRANSACTION');
	$vFlashDump = file_get_contents($vAssethashDumpFile);
	$vDataDB->queryExec('BEGIN TRANSACTION');
	$vResult = @$vDataDB->query('select * from units where field="iconurl" and name not in (select name from units where field="imageready")');
	while (@$vRow = $vResult->fetch(SQLITE_ASSOC)) {
		$vImage = $vRow['content'];
		$vName = $vRow['name'];
		if (!file_exists($vImage)) {
			$vHashImage = '';
			$vUrlPosition = strpos($vFlashDump, 'pushstring "' . $vImage);
			if ($vUrlPosition !== false) {
				$vUrlPosition += 12;
				$vUrlLength = strlen($vImage) - strlen(basename($vImage)) + 36;
				$vUrlPosition2 = strpos($vFlashDump, "pushstring", $vUrlPosition);
				if ($vUrlPosition2 !== false) $vHashImage = substr($vFlashDump, $vUrlPosition2 + 12, $vUrlLength);
			}
			if (strlen($vHashImage) > 0) {
				$vFolder = substr($vImage, 0, strrpos($vImage, "/"));
				if (!is_dir($vFolder)) @mkdir($vFolder, 0777, true);
				$vRemoteUrl = 'http://static.farmville.com/prod/hashed/' . $vHashImage;
				$vImageData = file_get_contents($vRemoteUrl);
				if ($vImageData) {
					file_put_contents($vImage, $vImageData);
					@$vDataDB->queryExec('insert into units("name","field","content") values("' . $vName . '","imageready","download")');
					AddLog2("parser: get images " . $vImage);
					error_log('"' . $vImage . '";"' . $vHashImage . '";"' . date('Y.m.d H:i:s') . '"' . "\n", 3, LogF('image_download_log.csv'));
				}
			}
		}
		else @$vDataDB->queryExec('insert into units("name","field","content") values("' . $vName . '","imageready","found")');
	}
	$vDataDB->queryExec('COMMIT TRANSACTION');
}

function Parser_Get_FlashVars() {
	AddLog2("Downloading latest flashVars.");
	$vHTML = proxy_GET_FB('http://apps.facebook.com/onthefarm/index.php?ref=bookmarks');
	preg_match_all('/<iframe[^>]*"([^"]*flash\.php[^"]*)"/ims', $vHTML, $vIFrames);
	unset($vHTML);
	$vHTML = proxy_GET_FB(html_entity_decode($vIFrames[1][0]));
	file_put_contents(F('flashVars.txt'), $vHTML);
	unset($vHTML);
}

function Parser_SQlite_Connect($vDBFile) {
	$vDB = new SQLiteDatabase($vDBFile);
	if (!$vDB) {
		AddLog2('Parser SQlite Error: cant open ' . $vDBFile);
		return (false);
	}
	$vDB->queryExec('PRAGMA cache_size=200000');
	$vDB->queryExec('PRAGMA synchronous=OFF');
	$vDB->queryExec('PRAGMA count_changes=OFF');
	$vDB->queryExec('PRAGMA journal_mode=MEMORY');
	$vDB->queryExec('PRAGMA temp_store=MEMORY');
	return $vDB;
}

function Units_GetSQL($vSQL) {
	global $vDataDB;
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		#$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
		unset($vTmpArray);
		foreach ($vRow as $vName => $vValue) {
			$vTmpArray[$vName] = $vValue;
		}
		$vReturn[] = $vTmpArray;
	}
	return ($vReturn);
}

function Units_GetUnitByName($vName, $vAllInfo = false) {
	global $vDataDB;
	if ($vAllInfo) $vSQL = 'select * from units where name="' . $vName . '"';
	else $vSQL = 'select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc") and name="' . $vName . '"';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		#$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
		$vReturn[$vRow['field']] = $vRow['content'];
	}
	return ($vReturn);
}

function Units_GetUnitByCode($vCode, $vAllInfo = false) {
	return (Units_GetUnitByName(Units_GetNameByCode($vCode), $vAllInfo));
}

function Units_GetRealnameByName($vName) {
	global $vDataDB;
	$vSQL = 'select content from units where name="' . $vName . '" and field="realname"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $vName : $vReturn);
}

function GetNeighborRealName($uid) {
	global $vDataDB;
	$vSQL = 'SELECT fullname FROM neighbors WHERE neighborid=\'' . $uid . '\' LIMIT 1';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $uid : $vReturn);
}

function Units_GetNameByRealname($vName) {
	global $vDataDB;
	$vSQL = 'select name from units where content="' . $vName . '" and field="realname"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $vName : $vReturn);
}

function Units_GetCodeByName($vName) {
	global $vDataDB;
	$vSQL = 'select content from units where name="' . $vName . '" and field="code"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $vName : $vReturn);
}

function Units_GetNameByCode($vCode) {
	global $vDataDB;
	$vSQL = 'select name from units where content="' . $vCode . '" and field="code"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $vCode : $vReturn);
}

function Units_GetRealnameByCode($vCode) {
	global $vDataDB;
	$vSQL = 'select content from units where field="realname" and name in (select name from units where content="' . $vCode . '" and field="code")';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $vCode : $vReturn);
}

function Units_GetAll($vAllInfo = false) {
	global $vDataDB;
	if ($vAllInfo) $vSQL = 'select * from units';
	else $vSQL = 'select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc")';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return ($vReturn);
}

function Units_GetByType($vType, $vAllInfo = false) {
	global $vDataDB;
	if ($vAllInfo) $vSQL = 'select * from units where name in (select name from units where field="type" and content="' . $vType . '")';
	else $vSQL = 'select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc") and name in (select name from units where field="type" and content="' . $vType . '")';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return ($vReturn);
}

function Units_GetByClass($vClass, $vAllInfo = false) {
	global $vDataDB;
	if ($vAllInfo) $vSQL = 'select * from units where name in (select name from units where field="className" and content="' . $vClass . '")';
	else $vSQL = 'select * from units where field in ("name","type","code","buyable","class","iconurl","market","cash","cost","subtype","growTime","coinYield","action","limitedEnd","requiredLevel","crop","sizeX","sizeY","plantXp","masterymax","license","realname","desc") and name in (select name from units where field="class" and content="' . $vClass . '")';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return ($vReturn);
}

function Units_GetFarming($vField) {
	global $vDataDB;
	$vSQL = 'select content from units where name="_farming" and field="' . $vField . '"';
	$vResult = $vDataDB->query($vSQL);
	$vReturn = $vResult->fetchSingle();
	return ($vReturn == '' ? $vField : $vReturn);
}

function Units_IsConsumableByName($vName) {
	global $vDataDB;
	$vSQL = 'select count(*) from units where name="' . $vName . '" and content="consumable" and field in ("type","subtype")';
	$vResult = @$vDataDB->query($vSQL);
	return (@$vResult->fetchSingle() == 0 ? false : true);
}

function Quests_GetQuestByName($vName) {
	global $vDataDB;
	$vSQL = 'select * from quests where name="' . $vName . '"';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
		#$vReturn[$vRow['name']][$vRow['field']]=$vRow['content'];
		$vReturn[$vRow['field']] = $vRow['content'];
	}
	return ($vReturn);
}

function Quests_GetAll() {
	global $vDataDB;
	$vSQL = 'select * from quests';
	$vResult = @$vDataDB->query($vSQL);
	while ($vRow = @$vResult->fetch(SQLITE_ASSOC)) {
		$vReturn[$vRow['name']][$vRow['field']] = $vRow['content'];
	}
	return ($vReturn);
}

function parse_flashvars() {
	clearstatcache();
	// Check if file exists, Get fresh file if older than 12 hours, Check if file is too small
	if ((!file_exists(F('flashVars.txt'))) || (filesize(F('flashVars.txt')) < 6) || (filemtime(F('flashVars.txt')) < (time() - 43200))) {
		Parser_Get_FlashVars();
		unlink('sqlite_check.txt');
	}
	$temp = file_get_contents(F('flashVars.txt'));
	preg_match('/var flashVars = (\{[^}]*\})/sim', $temp, $flash);
	return json_decode($flash[1], true);
}

function parse_neighbors() {
	global $vDataDB;
	$temp = file_get_contents(F('flashVars.txt'));
	preg_match('/var g_friendData = \[([^]]*)\]/sim', $temp, $friend);
	if (!isset($friend[1])) return;
	preg_match_all('/\{([^}]*)\}/sim', $friend[1], $friend2);
	foreach ($friend2[1] as $f) {
		preg_match_all('/"([^"]*)":"([^"]*)"/im', $f, $fr);
		$newarray[] = array_combine($fr[1], $fr[2]);
	}
	unset($friend2, $fr);
	$uSQL = '';
	foreach ($newarray as $friends) {
		if ($friends['is_app_user'] != 1) continue;
		$friends['pic_square'] = str_replace('\\/', '\\', $friends['pic_square']);
		$friends['name'] = str_replace("'", "''", $friends['name']);
		$friends['name'] = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $friends['name']);
		$uSQL .= "INSERT OR REPLACE INTO neighbors(neighborid, fullname, profilepic) values('" . $friends['uid'] . "',
                                '" . $friends['name'] . "', '" . $friends['pic_square'] . "');";
	}
	$vDataDB->queryExec($uSQL);
	unset($uSQL, $newarray);
	return;
}

function parser_download($vLocalFile, $vRemoteFile, $vOverrideURL = '') {
	if (strlen($vLocalFile) == 0) {
		AddLog2("DL: vLocalFile missing");
		return '';
	}
	if (strlen($vRemoteFile) == 0) {
		AddLog2("DL: vRemoteFile missing");
		return '';
	}
	global $vNotFound, $sqlite_update;
	if (!file_exists($vLocalFile)) {
		$vContent = '';
		if (strlen($vOverrideURL) > 0 && file_exists($vOverrideURL)) {
			$vContent = file_get_contents(trim(file_get_contents($vOverrideURL)));
			if (empty($vContent)) // Null if the file doesn't exist / server returned 404 error
				AddLog2("File: override_url.txt contains invalid url, skipping..");
			else AddLog2("Loaded settings from override_url.txt.");
		}
		if (empty($vContent)) {
			AddLog2("DL: $vRemoteFile");
			$vContent = proxy_GET($vRemoteFile);
		}
		if (empty($vContent)) {
			AddLog2("Couldn't find $vRemoteFile");
			$vNotFound++;
		}
		else {
			AddLog2("Download completed.");
			file_put_contents($vLocalFile, $vContent);
			$sqlite_update = 1;
		}
		unset($vContent);
	}
}

function pruneDir($vDir) {
	if (!is_dir($vDir)) @mkdir($vDir);
	$time_limit = 7 * 24 * 60 * 60; // number of seconds to 'keep' the log DAYSxHOURSxMINSxSECS
	if ($df = opendir($vDir)) {
		while (false !== ($file = readdir($df))) {
			if ($file != "." && $file != "..") {
				$file1 = $vDir . '/' . $file;
				$last_modified = filemtime($file1);
				if (time() - $last_modified > $time_limit) unlink($file1);
			}
		}
		closedir($df);
	}
}

function GetUnitList() {
	global $userId, $flashRevision, $botlitever;
	global $vDataDB;
	$argv = @$GLOBALS['argv'];
	if (strlen($userId) == 0 && strlen(@$argv[2]) > 0) $userId = @$argv[2];
	$flashRevision = @$argv[3];
	LoadAuthParams();
	define ('farmer', GetFarmserver());
	define ('farmer_url', GetFarmUrl());
	if (@$flashRevision == '' || @$flashRevision == 'null' || @$flashRevision == 'reload' || @$flashRevision == 'OK') {
		echo "Flash Revision unknown. A plugin likely has an error\r\n";
		AddLog2("Flash Revision unknown.\r\n A plugin likely has an error");
		RestartBot();
		return;
	}
	pruneDir('./farmville-xml');
	pruneDir('./farmville-sqlite');
	pruneDir('./farmville-logs');

	$sqlite_update = 0; //if 1 we are going to download new xml from server
	if (file_exists('sqlite_check.txt')) {
		@$sqlite_flashRevision = file_get_contents('sqlite_check.txt');
		if ($sqlite_flashRevision <> $flashRevision) $sqlite_update = 1;
	}
	else $sqlite_update = 1;
	if ($sqlite_update == 1) {
		$vDataDB = null;
		@copy(PARSER_SQLITE, './farmville-sqlite/' . date('Ymd_His') . '.sqlite');
		@unlink(PARSER_SQLITE);
		$vDataDB = Parser_SQlite_Connect(PARSER_SQLITE);
	}
	# check units table
	if (@$vDataDB->query('SELECT * FROM units limit 1') === false) {
		$vDataDB->queryExec('CREATE TABLE units ( name CHAR(25), field CHAR(25), content CHAR(250) )');
		$vDataDB->queryExec('CREATE INDEX units_idx_1 ON units(name,field)');
		$vDataDB->queryExec('CREATE INDEX units_idx_2 ON units(field,content)');
		$sqlite_update = 1;
	}
	if (@$vDataDB->query('SELECT * FROM neighbors limit 1') === false) $vDataDB->queryExec('CREATE TABLE neighbors (neighborid CHAR(25) PRIMARY KEY, fullname CHAR(50), profilepic TEXT)');
	# check achievements table
	if (@$vDataDB->query('SELECT * FROM achievements limit 1') === false) {
		$vDataDB->queryExec('CREATE TABLE achievements ( name CHAR(25), field CHAR(25), content CHAR(250) )');
		$vDataDB->queryExec('CREATE INDEX achievements_idx_1 ON achievements(name,field)');
		$vDataDB->queryExec('CREATE INDEX achievements_idx_2 ON achievements(field,content)');
		$sqlite_update = 1;
	}
	# check collectables table
	if (@$vDataDB->query('SELECT * FROM collectables limit 1') === false) {
		$vDataDB->queryExec('CREATE TABLE collectables ( name CHAR(25), field CHAR(25), content CHAR(250) )');
		$vDataDB->queryExec('CREATE INDEX collectables_idx_1 ON collectables(name,field)');
		$vDataDB->queryExec('CREATE INDEX collectables_idx_2 ON collectables(field,content)');
		$sqlite_update = 1;
	}
	# check storage table
	if (@$vDataDB->query('SELECT * FROM storage limit 1') === false) {
		$vDataDB->queryExec('CREATE TABLE storage ( name CHAR(25), field CHAR(25), content CHAR(250) )');
		$vDataDB->queryExec('CREATE INDEX storage_idx_1 ON storage(name,field)');
		$vDataDB->queryExec('CREATE INDEX storage_idx_2 ON storage(field,content)');
		$sqlite_update = 1;
	}
	# check crafting table
	if (@$vDataDB->query('SELECT * FROM crafting limit 1') === false) {
		$vDataDB->queryExec('CREATE TABLE crafting ( name CHAR(25), field CHAR(25), content CHAR(250) )');
		$vDataDB->queryExec('CREATE INDEX crafting_idx_1 ON crafting(name,field)');
		$vDataDB->queryExec('CREATE INDEX crafting_idx_2 ON crafting(field,content)');
		$sqlite_update = 1;
	}
	# check quests table
	if (@$vDataDB->query('SELECT * FROM quests limit 1') === false) {
		$vDataDB->queryExec('CREATE TABLE quests ( name CHAR(25), field CHAR(50), content CHAR(250) )');
		$vDataDB->queryExec('CREATE INDEX quests_idx_1 ON quests(name,field)');
		$vDataDB->queryExec('CREATE INDEX quests_idx_2 ON quests(field,content)');
		$sqlite_update = 1;
	}
	// Force download when key files are missing
	if (!file_exists('units.txt')) $sqlite_update = 1;
	if (!file_exists('collectable_info.txt')) $sqlite_update = 1;
	if (!file_exists('achievement_info.txt')) $sqlite_update = 1;
	AddLog2("Downloading latest game files.");
	$flashVars = parse_flashvars();
	$vNotFound = 0;

	parser_download('./farmville-xml/' . $flashRevision . '_flashLocaleXml.xml', "http://static.farmville.com/xml/gz/v$flashRevision/flashLocaleXml.xml", 'desc_url.txt');
	parser_download('./farmville-xml/' . $flashRevision . '_gameSettings.xml', $flashVars['game_config_url'], 'override_url.txt');
	parser_download('./farmville-xml/' . $flashRevision . '_items.xml', $flashVars['items_url'], 'override_url.txt');

	if (isset($flashVars['xml_url']) && !empty($flashVars['xml_url'])) $vRemoteFile = $flashVars['xml_url'] . 'StorageConfig.xml.gz';
	else $vRemoteFile = $flashVars['app_url'] . 'v' . $flashRevision . '/StorageConfig.xml.gz';

	parser_download('./farmville-xml/' . $flashRevision . '_StorageConfig.xml', $vRemoteFile);
	parser_download('./farmville-xml/' . $flashRevision . '_Quests.xml', $flashVars['social_quest_url']);

	if (isset($flashVars['xml_url']) && !empty($flashVars['xml_url'])) $vRemoteFile = $flashVars['xml_url'] . 'crafting.xml.gz';
	else $vRemoteFile = $flashVars['app_url'] . 'v' . $flashRevision . '/crafting.xml.gz';
	parser_download('./farmville-xml/' . $flashRevision . '_Crafting.xml', $vRemoteFile);

	parser_download('./farmville-xml/' . $flashRevision . '_MarketData.xml', "http://static.farmville.com/xml/gz/v$flashRevision/MarketData.xml.gz");
	parser_download('./farmville-xml/' . $flashRevision . '_Dialogs.xml', $flashVars['dialogs_url']);
	parser_download('./farmville-xml/' . $flashRevision . '_avatar.xml', "http://static.farmville.com/xml/gz/v$flashRevision/avatar.xml.gz");
	parser_download('./farmville-xml/' . $flashRevision . '_worldSettings.xml', "http://static.farmville.com/xml/gz/v$flashRevision/worldSettings.xml.gz");
	parser_download('./farmville-xml/' . $flashRevision . '_doobers.xml', "http://static.farmville.com/xml/gz/v$flashRevision/doobers.xml.gz");
	parser_download('./farmville-xml/' . $flashRevision . '_questSettings.xml', $flashVars['quest_url']);
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
		$xmlDoc = simplexml_load_file('./farmville-xml/' . $flashRevision . '_items.xml');
		foreach ($xmlDoc->items->item as $vItem) {
			$vItemName = (string)$vItem['name'];
			if (strlen($vItemName) > 0) {
				$vCntMastery = 0;
				$vCntRequirements = 0;
				foreach ($vItem->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				foreach ($vItem->children() as $vSubName => $vSubElement)
					switch ($vSubName) {
						case 'image':
							if ($vSubElement['name'] == 'icon') $vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","iconurl","' . $vSubElement['url'] . '");');
							break;
						case 'requirements':
							foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) if ($vSubSubName == 'requirement') {
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","requirement_' . $vCntRequirements . '_number","' . ($vCntRequirements + 1) . '");');
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","requirement_' . $vCntRequirements . '_className","' . $vSubSubElement['className'] . '");');
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","requirement_' . $vCntRequirements . '_name","' . $vSubSubElement['name'] . '");');
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","requirement_' . $vCntRequirements . '_level","' . $vSubSubElement['level'] . '");');
								$vCntRequirements++;
							}
							break;
						case 'masteryLevel':
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","masteryLevel_' . $vCntMastery . '_level","' . ($vCntMastery + 1) . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","masteryLevel_' . $vCntMastery . '_count","' . $vSubElement['count'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","masteryLevel_' . $vCntMastery . '_xp","' . $vSubElement['xp'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","masteryLevel_' . $vCntMastery . '_coins","' . $vSubElement['coins'] . '");');
							if ($vSubElement['gift']) {
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","masteryLevel_' . $vCntMastery . '_gift","' . $vSubElement['gift'] . '");');
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","masterymax","' . $vSubElement['count'] . '");');
							}
							$vCntMastery++;
							break;
						case 'storageType';
							if (strlen($vSubElement['itemClass']) > 0) $vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","storageType","' . $vSubElement['itemClass'] . '");');
							break;
						case 'upgrade':
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_level","' . $vSubElement['level'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubElement['level'] . '_capacity","' . $vSubElement['capacity'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubElement['level'] . '_matsNeeded","' . $vSubElement['matsNeeded'] . '");');
							foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
								if ($vSubSubName == 'part') {
									$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubElement['level'] . '_part","' . $vSubSubElement['name'] . '");');
									$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubElement['level'] . '_' . $vSubSubElement['name'] . '_need","' . $vSubSubElement['need'] . '");');
								}
							}
							break;
						case 'features':
							foreach ($vSubElement->children() as $vSubTmpName => $vSubTmpElement) foreach ($vSubTmpElement->children() as $vSubSubName => $vSubSubElement) if ($vSubSubName == 'upgrade') {
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_level","' . $vSubSubElement['level'] . '");');
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubSubElement['level'] . '_capacity","' . $vSubSubElement['capacity'] . '");');
								$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubSubElement['level'] . '_matsNeeded","' . $vSubSubElement['matsNeeded'] . '");');
								foreach ($vSubSubElement->children() as $vSubSubSubName => $vSubSubSubElement) if ($vSubSubSubName == 'part') {
									$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubSubElement['level'] . '_part","' . $vSubSubSubElement['name'] . '");');
									$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","upgrade_' . $vSubSubElement['level'] . '_' . $vSubSubSubElement['name'] . '_need","' . $vSubSubSubElement['need'] . '");');
								}
							}
							break;
						case 'defaultItem':
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","defaultItem_name","' . $vSubElement['name'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","defaultItem_amount","' . $vSubElement['amount'] . '");');
							break;
						default:
							if ($vSubName <> 'sounds') $vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . (string)$vSubElement . '");');
					}
			}
		}
		$xmlDoc = simplexml_load_file('./farmville-xml/' . $flashRevision . '_gameSettings.xml');
		foreach ($xmlDoc->farming as $vItem) foreach ($vItem->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into units(name,field,content) values("_farming","' . $vField . '","' . $vContent . '");');
		foreach ($xmlDoc->collections->collection as $vItem) {
			$vItemName = (string)$vItem['name'];
			if (strlen($vItemName) > 0) {
				foreach ($vItem->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if ($vSubName == 'collectable') {
						$vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vItemName . '","collectable","' . $vSubElement['code'] . '");');
						if (isset($vSubElement['chance'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","chance","' . $vSubElement['chance'] . '");');
						if (isset($vSubElement['rarity'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","rarity","' . $vSubElement['rarity'] . '");');
						if (isset($vSubElement['source'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","source","' . $vSubElement['source'] . '");');
						if (isset($vSubElement['numneeded'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vSubElement['code'] . '","numneeded","' . $vSubElement['numneeded'] . '");');
					}
					if ($vSubName == 'tradeInReward') {
						if (isset($vSubElement['xp'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vItemName . '","tradeInReward_xp","' . $vSubElement['xp'] . '");');
						if (isset($vSubElement['coins'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vItemName . '","tradeInReward_coins","' . $vSubElement['coins'] . '");');
						if (isset($vSubElement['gift'])) $vDataDB->queryExec('insert into collectables(name,field,content) values("' . $vItemName . '","tradeInReward_gift","' . $vSubElement['gift'] . '");');
					}
				}
			}
		}
		foreach ($xmlDoc->achievements->achievement as $vItem) {
			$vItemName = (string)$vItem['name'];
			$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","type","achieve");');
			if (strlen($vItemName) > 0) {
				foreach ($vItem->attributes() as $vField => $vContent) {
					$vDataDB->queryExec('insert into achievements(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
					$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				}
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if ($vSubName == 'image') {
						if ($vSubElement['name'] == 'icon_48') {
							$vDataDB->queryExec('insert into achievements(name,field,content) values("' . $vItemName . '","iconurl","' . $vSubElement['url'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vItemName . '","iconurl","' . $vSubElement['url'] . '");');
						}
					}
					elseif ($vSubName = 'level') {
						$vDataDB->queryExec('insert into achievements(name,field,content) values("' . $vItemName . '","count","' . $vSubElement['count'] . '");');
						$vDataDB->queryExec('insert into achievements(name,field,content) values("' . $vItemName . '","xp","' . $vSubElement['xp'] . '");');
						$vDataDB->queryExec('insert into achievements(name,field,content) values("' . $vItemName . '","coins","' . $vSubElement['coins'] . '");');
					}
				}
			}
		}
		$xmlDoc = simplexml_load_file('./farmville-xml/' . $flashRevision . '_StorageConfig.xml');
		foreach ($xmlDoc->StorageEntity as $vItem) {
			$vItemName = (string)$vItem['name'];
			if (strlen($vItemName) > 0) {
				$vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","type","StorageEntity");');
				foreach ($vItem->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				foreach ($vItem->children() as $vSubName => $vSubElement) if ($vSubName == 'allowedClass') $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . $vSubElement['type'] . '");');
				elseif ($vSubName == 'nonStorableClass' || $vSubName == 'denyKeyword' || $vSubName == 'allowKeyword') $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . (string)$vSubElement . '");');
			}
		}
		foreach ($xmlDoc->StorageBuilding as $vItem) {
			$vItemName = (string)$vItem['name'];
			if (strlen($vItemName) > 0) {
				$vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","type","StorageBuilding");');
				foreach ($vItem->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				foreach ($vItem->children() as $vSubName => $vSubElement) {
					if ($vSubName == 'allowedClass') $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . $vSubElement['type'] . '");');
					elseif ($vSubName == 'nonStorableClass' || $vSubName == 'denyKeyword' || $vSubName == 'allowKeyword') $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . (string)$vSubElement . '");');
					elseif ($vSubName == 'itemName') {
						if ($vSubElement['part'] == 'true') {
							$vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","part","' . (string)$vSubElement . '");');
							$vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . (string)$vSubElement . '_need","' . $vSubElement['need'] . '");');
						}
						else {
							$vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . (string)$vSubElement . '");');
							if ($vSubElement['limit']) $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . (string)$vSubElement . '_limit","' . $vSubElement['limit'] . '");');
						}
					}
				}
			}
		}
		foreach ($xmlDoc->FeatureCreditStorage as $vItem) {
			$vItemName = (string)$vItem['name'];
			if (strlen($vItemName) > 0) {
				$vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","type","FeatureCreditStorage");');
				foreach ($vItem->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vField . '","' . $vContent . '");');
				foreach ($vItem->children() as $vSubName => $vSubElement) $vDataDB->queryExec('insert into storage(name,field,content) values("' . $vItemName . '","' . $vSubName . '","' . (string)$vSubElement . '");');
			}
		}
		unset($xmlDoc);
		$xmlDoc = simplexml_load_file('./farmville-xml/' . $flashRevision . '_Crafting.xml');
		foreach ($xmlDoc->recipes->CraftingRecipe as $vRecipe) {
			$vRecipeID = (string)$vRecipe['id'];
			if (strlen($vRecipeID) > 0) {
				$vRecipeName = '';
				foreach ($vRecipe->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into crafting(name,field,content) values("' . $vRecipeID . '","' . $vField . '","' . $vContent . '");');
				foreach ($vRecipe->children() as $vSubName => $vSubElement) {
					if ($vSubName == 'name') $vRecipeName = (string)$vSubElement;
					if ($vSubName == 'image') {
						if ($vSubElement['name'] == 'icon') {
							$vDataDB->queryExec('insert into crafting(name,field,content) values("' . $vRecipeID . '","iconurl","' . $vSubElement['url'] . '");');
							$vDataDB->queryExec('insert into units(name,field,content) values("' . $vRecipeID . '","iconurl","' . $vSubElement['url'] . '");');
						}
					}
					elseif ($vSubName == 'Reward') foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) foreach ($vSubSubElement->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into crafting(name,field,content) values("' . $vRecipeID . '","reward_' . $vSubSubName . '_' . $vField . '","' . (string)$vContent . '");');
					elseif ($vSubName == 'Ingredients') foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
						$vDataDB->queryExec('insert into crafting(name,field,content) values("' . $vRecipeID . '","Ingredient_itemCode","' . $vSubSubElement['itemCode'] . '");');
						$vDataDB->queryExec('insert into crafting(name,field,content) values("' . $vRecipeID . '","Ingredient_quantityRequired_' . $vSubSubElement['itemCode'] . '","' . $vSubSubElement['quantityRequired'] . '");');
					}
					else $vDataDB->queryExec('insert into crafting(name,field,content) values("' . $vRecipeID . '","' . $vSubName . '","' . (string)$vSubElement . '");');
				}
				if (strlen($vRecipeName) > 0) $vDataDB->queryExec('update crafting set name="' . $vRecipeName . '" where name="' . $vRecipeID . '"');
			}
		}
		$xmlDoc = simplexml_load_file('./farmville-xml/' . $flashRevision . '_Quests.xml');
		foreach ($xmlDoc->quest as $vQuest) {
			$vQuestID = (string)$vQuest['id'];
			if (strlen($vQuestID) > 0) {
				foreach ($vQuest->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","' . $vField . '","' . $vContent . '");');
				foreach ($vQuest->children() as $vSubName => $vSubElement) {
					if ($vSubName == 'text') foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) foreach ($vSubSubElement->attributes() as $vField => $vContent) $vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","' . $vField . '","' . (string)$vContent . '");');
					elseif ($vSubName == 'icon') {
						$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","iconurl","' . $vSubElement['url'] . '");');
						$vDataDB->queryExec('insert into units(name,field,content) values("' . $vQuestID . '","iconurl","' . $vSubElement['url'] . '");');
					}
					elseif ($vSubName == 'questGiverImage') {
						$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","questGiverImage","' . $vSubElement['url'] . '");');
						$vDataDB->queryExec('insert into units(name,field,content) values("' . $vQuestID . '_Giver","iconurl","' . $vSubElement['url'] . '");');
					}
					elseif ($vSubName == 'completionRequirements') {
						$vCompleteName = $vSubElement['name'];
						$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '","' . $vCompleteName . '");');
						$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_timeLimit","' . $vSubElement['timeLimit'] . '");');
						foreach ($vSubElement->children() as $vSubSubName => $vSubSubElement) {
							if ($vSubSubName == 'requirement') $vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_' . (string)$vSubSubElement['action'] . '_' . (string)$vSubSubElement['type'] . '","' . (string)$vSubSubElement['many'] . '");');
							if ($vSubSubName == 'reward') {
								if ((string)$vSubSubElement['type'] == 'generic') {
									$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_reward_generic_coins","' . (string)$vSubSubElement['coins'] . '");');
									$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_reward_generic_experience","' . (string)$vSubSubElement['experience'] . '");');
								}
								if ((string)$vSubSubElement['type'] == 'crecipe') {
									$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_reward_crecipe_recipeId","' . (string)$vSubSubElement['recipeId'] . '");');
									$vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","completionRequirements_' . $vCompleteName . '_reward_crecipe_quantity","' . (string)$vSubSubElement['quantity'] . '");');
								}
							}
						}
					}
					else $vDataDB->queryExec('insert into quests(name,field,content) values("' . $vQuestID . '","' . $vSubName . '","' . (string)$vSubElement . '");');
				}
			}
		}
		unset($xmlDoc);
		$vDataDB->queryExec('COMMIT TRANSACTION');
		$vCollectable = array();
		$vResult = $vDataDB->query('select * from collectables where field="code"');
		while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
			$vCollectable[$vRow['content']]['name'] = $vRow['name'];
			$vCollectable[$vRow['content']]['code'] = $vRow['content'];
			$vResult2 = $vDataDB->query('select content from collectables where name="' . $vRow['name'] . '" and field="tradeInReward_xp"');
			$vCollectable[$vRow['content']]['tradeInReward'] = $vResult2->fetchSingle();
			$vResult4 = $vDataDB->query('select content from collectables where name="' . $vRow['name'] . '" and field="realname"');
			$vCollectable[$vRow['content']]['realname'] = $vResult4->fetchSingle();
			$vResult3 = $vDataDB->query('select content from collectables where name="' . $vRow['name'] . '" and field="collectable"');
			while ($vRow2 = $vResult3->fetch(SQLITE_ASSOC)) $vCollectable[$vRow['content']]['collectable'][] = $vRow2['content'];
		}
		file_put_contents('collectable_info.txt', serialize($vCollectable));
		unset($vCollectable);
		$vAchievements = array();
		$vSQL = 'select * from achievements where field="code"';
		$vResult = $vDataDB->query($vSQL);
		while ($vRow = $vResult->fetch(SQLITE_ASSOC)) {
			$vAchievements[$vRow['content']]['name'] = $vRow['name'];
			$vAchievements[$vRow['content']]['code'] = $vRow['content'];
			$vResult2 = $vDataDB->query('select content from achievements where name="' . $vRow['name'] . '" and field="iconurl"');
			$vAchievements[$vRow['content']]['iconurl'] = $vResult2->fetchSingle();
			$vResult2 = $vDataDB->query('select content from achievements where name="' . $vRow['name'] . '" and field="realname"');
			$vAchievements[$vRow['content']]['realname'] = $vResult2->fetchSingle();
			$vResult2 = $vDataDB->query('select content from achievements where name="' . $vRow['name'] . '" and field="desc"');
			$vAchievements[$vRow['content']]['desc'] = $vResult2->fetchSingle();
			$vResult2 = $vDataDB->query('select content from achievements where name="' . $vRow['name'] . '" and field="count" order by field');
			while ($vRow2 = $vResult2->fetch(SQLITE_ASSOC)) $vAchievements[$vRow['content']]['level'][] = $vRow2['content'];
		}
		file_put_contents('achievement_info.txt', serialize($vAchievements));
		unset($vAchievements);
		file_put_contents('units.txt', serialize(Units_GetAll()));
		file_put_contents('transforms.txt', serialize(GetTransforms(Units_GetAll())));
		file_put_contents('sqlite_check.txt', $flashRevision);
	}
	EchoData('OK');
}

function GetTransforms($array) {
	$ret = array();
	foreach ($array as $item) if (@$item['action'] == "transform") $ret[] = $item['name'];
	return $ret;
}

function Hook($hook) {
	global $plugins;
	global $this_plugin;
	foreach ($plugins as $plugin) if (isset($plugin['hooks'][$hook])) if (function_exists($plugin['hooks'][$hook])) {
		$this_plugin = $plugin;
		call_user_func($plugin['hooks'][$hook]);
	}
}

function GetSecretKeyLite() {
	global $userId, $flashRevision, $token;
	$argv = @$GLOBALS['argv'];
	if (strlen($userId) == 0 && strlen(@$argv[2]) > 0) $userId = @$argv[2];
	$flashRevision = @$argv[3];
	$token = @$argv[4];
	SaveAuthParams();
}

function load_array($filename) { return @unserialize(file_get_contents($GLOBALS['this_plugin']['folder'] . '/' . PluginF($filename))); }

function save_array($array, $filename) { file_put_contents($GLOBALS['this_plugin']['folder'] . '/' . PluginF($filename), serialize($array)); }

// ------------------------------------------------------------------------------
// AddLog add string to main log
//  @params string $str Text
// ------------------------------------------------------------------------------
function AddLog($str) {
	$GLOBALS['res_str'] .= $str . "\r\n";
	if ($GLOBALS['is_debug']) echo $str;
}

// ------------------------------------------------------------------------------
// AddLog2 add string to advanced log
//  @params string $str Text
// ------------------------------------------------------------------------------
function AddLog2($str) {
	@file_put_contents(LogF("log2.txt"), @date("H:i:s") . " $str\r\n", FILE_APPEND);
	if ($GLOBALS['is_debug'] || $GLOBALS['consolelog']) echo $str . "\r\n";
}

// ------------------------------------------------------------------------------
// DebugLog
//  @params string $str Text
// ------------------------------------------------------------------------------
function DebugLog($str) { if ($GLOBALS['is_debug']) echo $str . "\r\n"; }

// ------------------------------------------------------------------------------
// F creates a full file name
//  @param string $filename Short file name
//  @return string Full file name (UserID + '_' + Short name)
// ------------------------------------------------------------------------------
function F($filename) {
	global $userId, $vWorldtype;
	if (strlen($userId) == 0) error_log("parser_error: userId missing: F($filename)");
	if ($filename == 'units.txt') return 'units.txt';
	$vOverrideWorldtype = '';
	//TODO: Array
	if (in_array($filename, array('cookies.txt', 'flashVars.txt', 'gitft_reqs.txt', 'params.txt', 'rewards.txt', 'sendgifts.txt', 'worldtype.txt', 'worldcontinue.txt', 'worldswitch.txt'))) $vOverrideWorldtype = 'farm';
	if (strlen($vOverrideWorldtype) > 0) $folder = 'FBID_' . ($vOverrideWorldtype == 'england' ? 'xuk' : '') . $userId;
	else $folder = 'FBID_' . ($vWorldtype == 'england' ? 'xuk' : '') . $userId;
	if (!is_dir($folder)) @mkdir($folder);
	return $folder . '/' . $filename;
}

// ------------------------------------------------------------------------------
// PluginF creates a full file name (original F())
//  @param string $filename Short file name
//  @return string Full file name (UserID + '_' + Short name)
// ------------------------------------------------------------------------------
function PluginF($filename) { return $GLOBALS['userId'] . '_' . $filename; }

function LogF($filename) { return $GLOBALS['userId'] . '_' . $filename; }

// ------------------------------------------------------------------------------
// GetNeighbors gets a list of neighbors
//  @return array List of neighbors
// ------------------------------------------------------------------------------
function GetNeighbors() { return unserialize(file_get_contents(F('neighbors.txt'))); }

// ------------------------------------------------------------------------------
// GetObjects gets a list of objects on the farm
//  @param string $className Class name ('Plot', 'Animal', 'Tree' etc.)
//  @return array List of objects
// ------------------------------------------------------------------------------
function GetObjects($className = '') {
	// FarmFIX
	$my_farm_is_fucked_up = 0;
	if (file_exists('farmfix.txt')) $my_farm_is_fucked_up = trim(file_get_contents('farmfix.txt'));
	if ($my_farm_is_fucked_up == 1) return GetObjects2($className);
	// FarmFIX
	DebugLog(" >> GetObjects");
	$objects = load_botarray(F('objects.txt'));
	if ($className) {
		$resobjects = array();
		foreach ($objects as $object) if ($object['className'] == $className) $resobjects[] = $object;
		DebugLog(" << GetObjects");
		return $resobjects;
	}
	else {
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
	$object_file_1 = unserialize(file_get_contents(F('objects_1.txt')));
	$object_file_2 = unserialize(file_get_contents(F('objects_2.txt')));
	if ($className) {
		$resobjects = array();
		foreach ($object_file_1 as $object) if ($object['className'] == $className) $resobjects[] = $object;
		foreach ($object_file_2 as $object) if ($object['className'] == $className) $resobjects[] = $object;
		return $resobjects;
	}
	else {
		return array_merge($object_file_1, $object_file_2);
	}
}

// ------------------------------------------------------------------------------
// GetPlotName compiles plot name
//  @param array $plot
//  @return string Plot name
// ------------------------------------------------------------------------------
function GetPlotName($plot) { return $plot['position']['x'] . '-' . $plot['position']['y']; }

function Do_Farmhands_Arborists($vWhat) {
	global $userId;
	global $vCnt63000;
	if ($vCnt63000 < 63000) $vCnt63000 = 63000;
	$amf = CreateRequestAMF();
	$amf->_bodys[0]->_value[1][0]['functionName'] = 'WorldService.performAction';
	$amf->_bodys[0]->_value[1][0]['params'][0] = 'use';
	$amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
	$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = -1;
	if ($vWhat == 'farmhands') {
		$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CHarvestAnimals';
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = 'consume_farm_hands';
	}
	else {
		$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CHarvestTrees';
		$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = 'consume_arborists';
	}
	$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
	$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $vCnt63000++;
	$amf->_bodys[0]->_value[1][0]['params'][2]['targetUser'] = $userId;
	$amf->_bodys[0]->_value[1][0]['params'][2]['isFree'] = false;
	$amf->_bodys[0]->_value[1][0]['params'][2]['storageID'] = -1;
	$amf->_bodys[0]->_value[1][0]['params'][2]['isGift'] = true;
	$res = RequestAMF($amf);
	if ($res == 'OK') AddLog2($vWhat . " OK");
	else AddLog2($vWhat . " error: " . $res);
	return true;
}

function Do_Biplane_Instantgrow() {
	$biplane = GetObjects('Airplane');
	if (count($biplane) == 0) {
		AddLog2("no biplane found");
		AddLog("no biplane found");
		return false;
	}
	$biplane = $biplane[0];
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
	if ($res == 'OK') {
		$vSucess = $amf2->_bodys[0]->_value['data'][0]['success'];
		$vCost = $amf2->_bodys[0]->_value['data'][0]['cost'];
		if ($vSucess == 1) AddLog2("biplane success, COST: " . $vCost . " CASH");
		else AddLog2("biplane error: " . implode($amf2->_bodys[0]->_value['data'][0]));
	}
	else AddLog2("biplane error: " . $res);
	return $res;
}

function Do_Check_Lonlyanimals() {
	$amf = CreateRequestAMF();
	$amf->_bodys[0]->_value[1][0]['functionName'] = 'LonelyCowService.createLonelyAnimal';
	$amf->_bodys[0]->_value[1][0]['params'][0] = array();
	$amf->_bodys[0]->_value[2] = 0;
	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);
	if ($res == 'OK') {
		$vAnimal = $amf2->_bodys[0]->_value['data'][1];
		if (strlen($vAnimal) > 0) {
			AddLog("lonlyanimal found: " . $vAnimal);
			AddLog2("lonlyanimal found: " . $vAnimal);
		}
		else AddLog2("no lonlyanimal found");
	}
	else AddLog2("lonlyanimal error: " . $res);
	return $res;
}

function Do_Check_Wanderinganimals() {
	$amf = CreateRequestAMF();
	$amf->_bodys[0]->_value[1][0]['functionName'] = 'WanderingAnimalService.onCreateStallionReward';
	$amf->_bodys[0]->_value[1][0]['params'][0] = array();
	$amf->_bodys[0]->_value[2] = 0;
	$amf2 = RequestAMFIntern($amf);
	$res = CheckAMF2Response($amf2);
	if ($res == 'OK') {
		$vReward = $amf2->_bodys[0]->_value['data'][0]['data']['rewardUrl'];
		if (strlen($vReward) > 0) {
			AddLog("wanderinganimal found");
			AddLog2("wanderinganimal found");
		}
		else AddLog2("no wanderinganimal found");
	}
	else AddLog2("wanderinganimal error: " . $res);
	return $res;
}

function Do_Accept_Neighbor_Help() {
	global $userId;
	$vData = array();
	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] > 50) || (@$px_Setopts['bot_speed'] < 1)) $vSpeed = 1;
	else $vSpeed = $px_Setopts['bot_speed'];
	$vNActions = unserialize(file_get_contents(F('nactionqueue.txt')));
	foreach ($vNActions as $vActions) {
		$vNID = $vActions['visitorId'];
		foreach ($vActions['actions'] as $vAction) {
			$vData[] = array(0 => $vNID, 1 => $vAction['actionType'], 2 => $vAction['objectId']);
		}
	}
	while (count($vData) > 0) {
		$amf = new AMFObject("");
		$amf->_bodys[0] = new MessageBody();
		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';
		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;
		$vCntSpeed = 0;
		$OKstring = '';
		while (count($vData) > 0 && $vCntSpeed < $vSpeed) {
			$vParams = array_pop($vData);
			$amf->_bodys[0]->_value[1][$vCntSpeed]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vCntSpeed]['functionName'] = "NeighborActionService.clearNeighborAction";
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'] = $vParams;
			$OKstring .= "\r\n" . 'accept help ' . $vParams[1] . ' from ' . GetNeighborRealName($vParams[0]) . ' (' . $vParams[0] . ') on plot ' . $vParams[2];
			$vCntSpeed++;
		}
		$res = RequestAMF($amf);
		unset($amf->_bodys[0]->_value[1]);
		$need_reload = true;
		if ($res === 'OK') {
			AddLog($OKstring);
			AddLog2('accept neighbor help: OK');
		}
		else {
			if ($res) {
				AddLog2("Error: $res on accept neighbor help");
				if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) // Server sequence was reset
					DoInit();
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
function Do_Farm_Work($plots, $action = 'harvest') {
	global $need_reload;
	global $userId;
	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1)) $px_Setopts['bot_speed'] = 1;
	if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED) $px_Setopts['bot_speed'] = PARSER_MAX_SPEED;
	$sequence = GetSequense();
	$count = ceil(count($plots) / $px_Setopts['bot_speed']);
	//Initialize values for exponential smoothing
	$t = 1.5;
	$a = 0.1;
	if ($count == 0) return;
	$amf = new AMFObject('');
	$amf->_bodys[0] = new MessageBody();
	$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
	$amf->_bodys[0]->responseURI = '/1/onStatus';
	$amf->_bodys[0]->responseIndex = '/1';
	$amf->_bodys[0]->_value[0] = GetAMFHeaders();
	$amf->_bodys[0]->_value[2] = 0;
	foreach (array_chunk($plots, $px_Setopts['bot_speed']) as $chunk) {
		for ($i = 0; $i < count($chunk); $i++) {
			$amf->_bodys[0]->_value[1][$i]['functionName'] = 'WorldService.performAction';
			$amf->_bodys[0]->_value[1][$i]['params'][0] = $action;
			$amf->_bodys[0]->_value[1][$i]['sequence'] = $sequence++;
			$amf->_bodys[0]->_value[1][$i]['params'][1] = $chunk[$i];
			$amf->_bodys[0]->_value[1][$i]['params'][2] = array();
			$amf->_bodys[0]->_value[1][$i]['params'][2][0]['energyCost'] = 0;
			//$OKstring .= "\r\n" . $action . ' ' . $chunk[$i]['itemName'] . ' on plot ' . GetPlotName($chunk[$i]);
		}
		$time = microtime(true);
		$res = RequestAMF($amf);
		$time = microtime(true) - $time;
		$t = $a * $t + $time * (1 - $a);
		AddLog2('Seq: ' . $sequence . ' ' . (round($time * 1000)) . 'ms / ' . round($t * round(($count-- + 1))) . 's remaining. ' . PARSER_MAX_SPEED . 'x ' . $action . ' ' . $chunk[0]['itemName']);
		unset($amf->_bodys[0]->_value[1]);
		if ($res != 'OK') {
			AddLog2('Error: ' . $res . ' on ' . $chunk[0]['itemName'] . ' ' . GetPlotName($chunk[0]));
			if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) { // Server sequence was reset
				SetSequense($sequence);
				DoInit();
				$sequence = GetSequense();
			}
		}
	}
	SetSequense($sequence);
	SaveAuthParams();
}

// ------------------------------------------------------------------------------
// Do_Farm_Work_Plots
//  @param array $plots
//  @param string $action (optional)
// ------------------------------------------------------------------------------
function Do_Farm_Work_Plots($plots, $action = 'harvest') {
	global $need_reload;
	global $vCnt63000;
	if (@strlen($vCnt63000) == 0) $vCnt63000 = 63000;
	$px_Setopts = LoadSavedSettings();
	if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1)) $px_Setopts['bot_speed'] = 1;
	if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED) $px_Setopts['bot_speed'] = PARSER_MAX_SPEED;
	$vMaxEquip = 16;
	foreach (array('combine', 'plow', 'place', 'harvest') as $fuelaction) $fuel = @$px_Setopts['fuel_' . $fuelaction];
	if ($action == 'tractor') $fuel = @$px_Setopts['fuel_plow'];
	if ((@!$fuel) || (@$fuel < 0)) $fuel = 0;
	if ($fuel == 0 && $action == 'combine') return;
	if ($fuel == 0 && $action == 'tractor') return;
	if ($fuel == 0) return Do_Farm_Work($plots, $action);
	while (count($plots) > 0) {
		global $userId;
		$amf = new AMFObject('');
		$amf->_bodys[0] = new MessageBody();
		$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
		$amf->_bodys[0]->responseURI = '/1/onStatus';
		$amf->_bodys[0]->responseIndex = '/1';
		$amf->_bodys[0]->_value[0] = GetAMFHeaders();
		$amf->_bodys[0]->_value[2] = 0;
		$vCntSpeed = 0;
		while (count($plots) > 0 && $vCntSpeed < $px_Setopts['bot_speed'] && $fuel > 0) {
			$amf->_bodys[0]->_value[1][$vCntSpeed]['sequence'] = GetSequense();
			$amf->_bodys[0]->_value[1][$vCntSpeed]['functionName'] = "EquipmentWorldService.onUseEquipment";
			if ($action == 'tractor') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][0] = 'plow';
			else $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][0] = $action;
			$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['id'] = -1;
			if ($action == 'combine') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'q1:96'; # fully expanded combine
			if ($action == 'harvest') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'V1:32'; # fully expanded harvester
			if ($action == 'tractor') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'T1:32'; # fully expanded tractor
			if ($action == 'plow') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'T1:32'; # fully expanded tractor
			if ($action == 'place') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][1]['key'] = 'S1:32'; # fully expanded seeder
			$vCntEquip = 0;
			$vSeed = '';
			$vLastSeed = '';
			while (count($plots) > 0 && $vCntEquip < $vMaxEquip && $fuel > 0) {
				$vPlot = array_pop($plots);
				if ($action == 'place' || $action == 'combine') {
					$vSeed = $vPlot['itemName'];
					if ($vLastSeed == '') $vLastSeed = $vSeed;
					elseif ($vLastSeed <> $vSeed) {
						array_push($plots, $vPlot);
						break;
					}
				}
				if (!isset($plotsstring)) $plotsstring = $vPlot['itemName'] . " " . GetPlotName($vPlot);
				else $plotsstring = $plotsstring . ", " . $vPlot['itemName'] . " " . GetPlotName($vPlot);
				if (!isset($OKstring)) $OKstring = $action . " " . $vPlot['itemName'] . " on plot " . GetPlotName($vPlot);
				else $OKstring = $OKstring . "\r\n" . $action . " " . $vPlot['itemName'] . " on plot " . GetPlotName($vPlot);
				$fuel--;
				if ($action == 'tractor') {
					$vPlot['id'] = $vCnt63000++;
					$amf->_bodys[0]->_value[1][$vCntSpeed]['params'][2][$vCntEquip] = $vPlot;
				}
				else $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][2][$vCntEquip]['id'] = $vPlot['id'];
				$vCntEquip++;
			}
			if ($action == 'combine') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = $vSeed;
			if ($action == 'tractor') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'harvest') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'plow') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = 'plowed';
			if ($action == 'place') $amf->_bodys[0]->_value[1][$vCntSpeed]['params'][3] = $vSeed;
			$vCntSpeed++;
		}
		AddLog2($action . ' ' . $plotsstring);
		$res = RequestAMF($amf);
		AddLog2("Result $res");
		unset($amf->_bodys[0]->_value[1]);
		$need_reload = true;
		if ($res === 'OK') AddLog($OKstring);
		elseif ($res) {
			AddLog("Error: $res on " . $OKstring);
			if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false)) // Server sequence was reset
				DoInit();
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

function RequestAMF($amf) { return CheckAMF2Response(RequestAMFIntern($amf)); }

// ------------------------------------------------------------------------------
// RequestAMFIntern sends AMF request to the farmville server
//  @param object $request AMF request
//  @return object $amf2
// ------------------------------------------------------------------------------
function RequestAMFIntern($amf) {
	DebugLog(" >> RequestAMF");
	$serializer = new AMFSerializer();
	$answer = $GLOBALS['curlfetcher']->post(farmer_url, $serializer->serialize($amf), 'application/x-amf');
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
		if ($amf2->_bodys[0]->_value['errorData'] == "There is a new version of the farm game released" || $amf2->_bodys[0]->_value['errorData'] == "Client has a newer version than backend") {
			AddLog2("New version of the game released");
			echo "\n*****\nGame version out of date\n*****\n";
			unlink('sqlite_check.txt');
			RestartBot();
		}
		else if ($amf2->_bodys[0]->_value['errorData'] == "token value failed") {
			AddLog2("Error: token value failed");
			AddLog2("You opened the game in another browser");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: token value failed\nThis error is caused by opening the game in another browser\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		}
		else if ($amf2->_bodys[0]->_value['errorData'] == "token too old") {
			AddLog2("Error: token too old");
			AddLog2("The session expired");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: token too old\nThe session has expired\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		}
		else if ($amf2->_bodys[0]->_value['errorType'] == 29) {
			AddLog2("Error: Server sequence was reset");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: Server sequence was reset\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		}
		else if ($amf2->_bodys[0]->_value['errorType'] == 27) {
			AddLog2("Error: User session not in memcache");
			AddLog2("Restart the game or wait for forced restart");
			echo "\n*****\nError: User session not in memcache\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
			RestartBot();
		}
		else {
			echo "\n*****\nError: \n" . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'] . "\n";
			$res = "Error: " . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'];
		}
	}
	else if (!isset($amf2->_bodys[0]->_value['data'][0])) {
		echo "\n*****\nError:\n BAD AMF REPLY - Possible Server problem or farm badly out of sync\n*****\n";
		$res = "BAD AMF REPLY (OOS?)";
	}
	else if (isset($amf2->_bodys[0]->_value['data'][0]['data']) && ($amf2->_bodys[0]->_value['data'][0]['data'] == 'success') || ($amf2->_bodys[0]->_value['data'][0]['data'] == '6uccess')) $res = 'OK';
	else if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] <> 0)) $res = $amf2->_bodys[0]->_value['data'][0]['errorType'] . " " . $amf2->_bodys[0]->_value['data'][0]['errorData'] . ' ' . $amf2->_bodys[0]->_value['data'][0]['data']['error'];
	else if (isset($amf2->_bodys[0]->_value['data'][0]['data']['error']) && (strlen($amf2->_bodys[0]->_value['data'][0]['data']['error']) > 0)) $res = $amf2->_bodys[0]->_value['data'][0]['data']['error'];
	else
		$res = 'OK';
	return $res;
}

function CheckAMF2RewardsSubCheck($vRewURL, $vRewItem, $vItemUrl, &$vFound, &$vRewardsArray) {
	if (strlen($vRewURL) > 0 && strlen($vRewItem) > 0 && substr($vRewURL, 0, 10) == 'reward.php' && strpos($vRewURL, ' ') === false) {
		$vRewardsArray[] = array('rewardLink' => $vRewURL, 'rewardItem' => $vRewItem, 'timestamp' => time());
		$vFound[] = $vRewItem;
	}
}

function CheckAMF2RewardsSubCheck2($vRewURL, $vRewItem, $vItemUrl, &$vFound, &$vRewardsArray) {
	if (strlen($vRewURL) > 0 && substr($vRewURL, 0, 10) == 'reward.php' && strpos($vRewURL, ' ') === false && strpos($vRewURL, $vRewItem) !== false) {
		$vRewardsArray[] = array('rewardLink' => $vRewURL, 'rewardItem' => $vRewItem, 'timestamp' => time());
		$vFound[] = $vRewItem;
	}
}

function CheckAMF2RewardsSub($vReward, &$vFound, &$vRewardsArray) {
	error_reporting(E_WARNING);
	CheckAMF2RewardsSubCheck($vReward['collectionCounters']['0']['link'], $vReward['collectionCounters']['0']['collectable'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['collectionCounters'][0]['link'], $vReward['collectionCounters'][0]['collectable'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['buyResponse']['buyResults'][0]['rewardLink'], $vReward['data']['buyResponse']['buyResults'][0]['recipe'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['data']['fuelDiscoveryRewardLink'], '2A', 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['data']['fuelRewardLink'], '2A', 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['data']['rewardLink'], $vReward['data']['data']['rewardItem'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['expansionRewardLink'], $vReward['data']['rewardItem'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['fertilizeRewardLink'], $vReward['data']['goodieBagRewardItemCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['foalRewardLink'], $vReward['data']['foalCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['foundBushel']['bushelsFullRewardUrl'], $vReward['data']['foundBushel']['bushelCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['foundBushel']['openStallRewardUrl'], $vReward['data']['foundBushel']['bushelCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['fuelRewardLink'], '2A', 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['itemBuffRewardUrl'], $vReward['data']['itemBuffCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['itemFoundRewardUrl'], $vReward['data']['itemShareName'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['mysterySeed']['rewardLink'], $vReward['data']['mysterySeed']['itemCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['reward']['rewardLink'], $vReward['data']['reward']['itemCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'], $vReward['data']['animalName'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'], $vReward['data']['bonusCoins'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'], $vReward['data']['gift'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'], $vReward['data']['harvestItem'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'], $vReward['data']['itemCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardLink'], $vReward['data']['rewardItem'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'], $vReward['data']['animalName'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'], $vReward['data']['itemCode'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'], $vReward['data']['rewardItem'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data']['rewardUrl'], $vReward['data']['rewardType'], 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['data'][0]['rewardLink'], $vReward['data'][0]['recipeId'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck($vReward['goals'][0]['link'], $vReward['goals'][0]['code'], 'Code', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardLink'], 'ConstructionBuildingFriendReward', 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardLink'], 'FeedTroughFriendReward', 'Item', $vFound, $vRewardsArray);
	CheckAMF2RewardsSubCheck2($vReward['data']['rewardLink'], 'PigpenSlopFriendReward', 'Item', $vFound, $vRewardsArray);
	if (isset($vReward['data']['rewardUrl'])) foreach (array('BushelFriendReward', 'CellarRedeemFriendReward', 'EasterBasketRedeemFriendReward', 'HaitiBackpackRedeemFriendReward', 'HalloweenBasketRedeemFriendReward', 'OilBarronFriendReward', 'Item', 'PigpenSlopFriendReward', 'PotOfGoldRedeemFriendReward', 'SocialMissionShareBonusFriendReward', 'TuscanWeddingRedeemFriendReward', 'ValentineRedeemFriendReward', 'WanderingStallionFriendReward', 'thanksgivingbasketRedeemFriendReward') as $rewardType) CheckAMF2RewardsSubCheck2($vReward['data']['rewardUrl'], $rewardType, 'Item', $vFound, $vRewardsArray);
}

function CheckAMF2Rewards($amf2) {
	if (!isset($amf2->_bodys[0]->_value['data'])) return;
	if (isset($amf2->_bodys[0]->_value['data']['0']['data']['userInfo'])) return;
	$vRewardsArray = array();
	$vRewardsArrayNew = array();
	$vFound = array();
	$vSeen = array();
	foreach ($amf2->_bodys[0]->_value['data'] as $vReward) {
		$vFound = false;
		if (stripos(print_r($vReward, true), 'reward.php') === false) continue;
		CheckAMF2RewardsSub($vReward, $vFound, $vRewardsArray);
		foreach (array('harvest', 'plow', 'place', 0) as $rewardsubtype) if (isset($vReward['data'][$rewardsubtype]) && is_array($vReward['data']['harvest']['data'])) foreach ($vReward['data']['harvest']['data'] as $vSubReward) if (stripos(print_r($vSubReward, true), 'reward.php') !== false) CheckAMF2RewardsSub($vSubReward, $vFound, $vRewardsArray);
		if (!$vFound) {
//file_put_contents('rew_data_raw_' . date('z') . '.txt', print_r($vReward, true));
			preg_match_all('/reward.php\?frHost=([^&]*)&frId=([^&]*)&frType=([^& ]*)/si', str_replace(array("\r", "\n"), array(' ', ' '), print_r($vReward, true)), $vRewards);
			for ($vI = 0; $vI < count($vRewards[1]); $vI++) {
				$vUserID = $vRewards[1][$vI];
				$vRewID = $vRewards[2][$vI];
				$vRewType = $vRewards[3][$vI];
				if (strpos($vUserID . $vRewID . $vRewType, '"') !== false) continue;
				if (strpos($vUserID . $vRewID . $vRewType, '&') !== false) continue;
				if (strpos($vUserID . $vRewID . $vRewType, ' ') !== false) continue;
				if (strpos($vUserID . $vRewID . $vRewType, '=') !== false) continue;
				$vRewURL = 'reward.php?frHost=' . $vUserID . '&frId=' . $vRewID . '&frType=' . $vRewType;
				$vRewardsArray[] = array('rewardLink' => $vRewURL, 'rewardItem' => 'Unknown', 'timestamp' => time());
			}
		}
		else {
			AddLog2('Parser_CheckAMF2Rewards: rewards found: ' . implode('|', $vFound));
			//file_put_contents('raw_rewards' . time(), serialize($amf2->_bodys[0]->_value['data']));
		}
	}
	if (count($vRewardsArray) > 0 && is_array($vRewardsArray)) {
		$vRewardsArrayOld = unserialize(file_get_contents(F('rewards.txt')));
		if (count($vRewardsArrayOld) > 0) {
			foreach ($vRewardsArrayOld as $vRewardTmp) {
				if (!in_array($vRewardTmp['rewardLink'], $vSeen) && substr($vRewardTmp['rewardLink'], 0, 10) == 'reward.php' && strpos($vRewardTmp['rewardLink'], ' ') === false && $vRewardTmp['timestamp'] > (time() - (60 * 60 * 24))) {
					$vRewardsArrayNew[] = $vRewardTmp;
					$vSeen[] = $vRewardTmp['rewardLink'];
				}
			}
		}
		foreach ($vRewardsArray as $vRewardTmp) {
			if (!in_array($vRewardTmp['rewardLink'], $vSeen) && substr($vRewardTmp['rewardLink'], 0, 10) == 'reward.php' && strpos($vRewardTmp['rewardLink'], ' ') === false && $vRewardTmp['timestamp'] > (time() - (60 * 60 * 24))) {
				$vRewardsArrayNew[] = $vRewardTmp;
				$vSeen[] = $vRewardTmp['rewardLink'];
			}
		}
		save_botarray($vRewardsArrayNew, F('rewards.txt'));
	}
}

function Parser_GetCookieString() {
	if (file_exists(F('cookies.txt'))) {
		$cookiestr = '';
		$vCookieArry = file(F('cookies.txt'));
		foreach ($vCookieArry as $vCookieString) $cookiestr .= substr($vCookieString, 0, -16) . '; ';
	}
	else AddLog2('Parser_GetCookieString: no cookies.txt');
	return $cookiestr;
}

function Parser_ReadReq() {
	$vHTML = proxy_GET_FB('http://www.facebook.com/reqs.php');
	preg_match_all('/(<form rel="async" action="\/ajax\/reqs\.php" method="post".*?<\/form>)/ims', $vHTML, $vForms);
	unset($vHTML);
	$vGiftRequests = array();
	foreach ($vForms[0] as $vI => $vForm) {
		preg_match_all('/name="([^"]*)" value="([^"]*)"/ims', $vForm, $vNameValues);
		preg_match_all('|<input[^>]*value="([^\"]*)"[^>]*name="actions\[([^>]*)][^>]*>?|ims', $vForm, $vActions);
		preg_match_all('|<a href="http://apps\.facebook\.com/.*?>(.*?)</a>|ims', $vForm, $vAppNameValues);
		preg_match_all('/name="fb_dtsg" value="([^"]*)"/ims', $vForm, $vDTSGValues);
		preg_match_all('|<span fb_protected="true" class="fb_protected_wrapper">(.*?)</span>|ims', $vForm, $vGiftText);
		$vGiftText = trim(strip_tags($vGiftText[1][0]));
		$vPost = '';
		$vAppId = '';
		for ($vJ = 0; $vJ < count($vNameValues[1]); $vJ++) {
			if ($vNameValues[1][$vJ] == 'params[app_id]') $vAppId = $vNameValues[2][$vJ];
			if ($vPost != '') $vPost .= '&';
			$vPost .= $vNameValues[1][$vJ] . '=' . urlencode(html_entity_decode($vNameValues[2][$vJ], ENT_QUOTES, 'UTF-8'));
		}
		if ($vAppId == '102452128776') {
			$vActionName = '';
			$vActionUrl = '';
			for ($vJ = 0; $vJ < count($vActions[1]); $vJ++) {
				if ($vActions[2][$vJ] != 'reject') {
					$vActionName = $vActions[1][$vJ];
					$vActionUrl = html_entity_decode($vActions[2][$vJ]);
					$vPost .= '&actions[' . urlencode(html_entity_decode($vActions[2][$vJ], ENT_QUOTES, 'UTF-8')) . ']=' . str_replace('+', '%20', urlencode($vActions[1][$vJ]));
					break;
				}
			}
			$vAppName = '';
			if (count($vAppNameValues) > 0) $vAppName = $vAppNameValues[1][0];
			if (count($vDTSGValues) > 0) $vDTSG = $vDTSGValues[1][0];
			$vPost .= '&post_form_id_source=AsyncRequest';
			$vGiftRequests[$vI] = array('form' => $vForm, 'name' => 'FarmVille', 'app_id' => $vAppId, 'app_name' => $vAppName, 'action_name' => $vActionName, 'gift_text' => $vGiftText, 'action_url' => $vActionUrl, 'post_data' => $vPost, 'fb_dtsg' => $vDTSG);
		}
		unset($vNameValues, $vActions, $vAppNameValues, $vDTSGValues, $vPost);
	}
	unset($vForms);
	return ($vGiftRequests);
}

function Parser_SendGift() {
	global $vDataDB, $need_reload;
	if (!(file_exists('sendgifts.txt') || file_exists(F('sendgifts.txt')))) return '';
	AddLog2('Parser_send_gift: check neighbors');
	$vGift = 'socialplumbingmysterygift';
	$vURL = 'http://apps.facebook.com/onthefarm/';
	$vHTML = proxy_GET_FB($vURL);
	preg_match_all('%<form action="([^"].*?)" method="post" target="flashAppIframe".*?>(.*)</form>%im', $vHTML, $vIframes);
	if (!empty($vIframes[1][0]) && !empty($vIframes[2][0])) {
		preg_match_all('%<input type="hidden".*?name="([^"].*?)".*?value="([^"].*?)"%im', $vIframes[2][0], $vIframes2);
		$vPosts = array_combine($vIframes2[1], $vIframes2[2]);
	}
	preg_match_all('/post_form_id:"([^"]*)"/ims', $vHTML, $vPostFormIDs);
	preg_match_all('/fb_dtsg:"([^"]*)"/ims', $vHTML, $vDTGSs);
	$vPostFormID = $vPostFormIDs[1][0];
	$vDTGS = $vDTGSs[1][0];
	$vURL = html_entity_decode($vIframes[1][0]);
	$vHTML = proxy_GET_FB($vURL, 'POST', $vPosts);
	preg_match_all('/class="gifts_tab " href="(.*)" title="Free Gifts"/is', $vHTML, $vZys);
	$vURL = str_replace('/gifts.php?ref=tab&', '/gifts_send.php?gift=' . $vGift . '&view=farmville&src=direct&aff=&crt=&sendkey=&', $vZys[1][0]) . '&overlayed=true&' . time();
	$vHTML = proxy_GET_FB($vURL);
	preg_match_all('/FB.init\("([^"]*)", "([^"]*)"\)/is', $vHTML, $vFBInits);
	$vAppKey = 'app_key=' . $vFBInits[1][0];
	$vChannelUrl = 'channel_url=' . $vFBInits[2][0];
	$vStart = strpos($vHTML, '<fb:serverfbml><script type="text/fbml">') + strlen('<fb:serverfbml><script type="text/fbml">');
	$vStop = strpos($vHTML, '</script></fb:serverfbml>', $vStart);
	$vFBML = 'fbml=' . urlencode(substr($vHTML, $vStart, $vStop - $vStart));
	$vHTML = proxy_GET_FB("http://www.connect.facebook.com/widgets/serverfbml.php", 'POST', $vAppKey . '&' . $vChannelUrl . '&' . $vFBML);
	preg_match_all('/var items=({.*});/im', $vHTML, $vNeighbor1s);
	preg_match_all('/"([0-9]+)":{"name":"([^"]*)"}/is', $vNeighbor1s[1][0], $vNeighbor2s);
	$vCnt = 0;
	$vSQL = '';
	foreach ($vNeighbor2s[1] as $vNUID) {
		$vSQL .= "INSERT OR REPLACE INTO neighbors(neighborid, fullname) values('" . $vNUID . "','" . str_replace("'", "''", $vNeighbor2s[2][$vCnt]) . "');";
		$vCnt++;
	}
	if (strlen($vSQL) > 0) {
		$vDataDB->queryExec($vSQL);
		unset($vSQL, $newarray);
	}
	$vSendNeighborsArray = $vNeighbor2s[1];
	if (file_exists(F('sendgifts.txt'))) {
		if (file_exists('sendgifts.txt')) $vGiftsArray = file('sendgifts.txt');
		else $vGiftsArray = file(F('sendgifts.txt'));
		foreach ($vGiftsArray as $vRow) {
			list($vFBID, $vGift) = explode(';', trim($vRow));
			$vGift = trim($vGift);
			$vFBID = (trim($vFBID));
			if (strlen($vFBID) > 0 && strlen($vGift) > 0 && in_array($vFBID, $vSendNeighborsArray) && count($vSend[$vGift]) < 25) $vSend[$vGift][] = $vFBID;
		}
	}
	if (!empty($vSend)) foreach ($vSend as $vGift => $vFBIDs) if (count($vFBIDs) > 0) {
		Parser_SendGift_Do($vFBIDs, $vGift);
	}
}

function Parser_SendGift_Do($vFBIDs, $vGift) {
	$vLog = 'Parser_send_gift: send ' . Units_GetRealnameByName($vGift) . ' (' . $vGift . ') to ';
	foreach ($vFBIDs as $vFBID) {
		$vLog .= GetNeighborRealName($vFBID) . ' (' . $vFBID . ') ,';
		error_log('"' . GetNeighborRealName($vLog) . '";"' . $vLog . '";"' . $vGift . '";"' . date('Y.m.d H:i:s') . '"' . "\n", 3, LogF('gifts_send.csv'));
	}
	AddLog2(trim($vLog, ', '));
	$vURL = 'http://apps.facebook.com/onthefarm/';
	$vHTML = proxy_GET_FB($vURL);
	preg_match_all('%<form action="([^"].*?)" method="post" target="flashAppIframe".*?>(.*)</form>%im', $vHTML, $vIframes);
	if (!empty($vIframes[1][0]) && !empty($vIframes[2][0])) {
		preg_match_all('%<input type="hidden".*?name="([^"].*?)".*?value="([^"].*?)"%im', $vIframes[2][0], $vIframes2);
		$vPosts = array_combine($vIframes2[1], $vIframes2[2]);
	}
	preg_match_all('/post_form_id:"([^"]*)"/ims', $vHTML, $vPostFormIDs);
	preg_match_all('/fb_dtsg:"([^"]*)"/ims', $vHTML, $vDTGSs);
	$vPostFormID = $vPostFormIDs[1][0];
	$vDTGS = $vDTGSs[1][0];
	$vURL = html_entity_decode($vIframes[1][0]);
	$vHTML = proxy_GET_FB($vURL, "POST", $vPosts);
	preg_match_all('/class="gifts_tab " href="(.*)" title="Free Gifts"/is', $vHTML, $vZys);
	$vURL = str_replace('/gifts.php?ref=tab&', '/gifts_send.php?gift=' . $vGift . '&view=farmville&src=direct&aff=&crt=&sendkey=&', $vZys[1][0]) . '&overlayed=true&' . time();
	$vHTML = proxy_GET_FB($vURL);
	preg_match_all('/FB.init\("([^"]*)", "([^"]*)"\)/is', $vHTML, $vFBInits);
	$vAppKey = 'app_key=' . $vFBInits[1][0];
	$vChannelUrl = 'channel_url=' . $vFBInits[2][0];
	$vStart = strpos($vHTML, '<fb:serverfbml><script type="text/fbml">') + strlen('<fb:serverfbml><script type="text/fbml">');
	$vStop = strpos($vHTML, '</script></fb:serverfbml>', $vStart);
	$vFBML = 'fbml=' . urlencode(substr($vHTML, $vStart, $vStop - $vStart));
	$vHTML = proxy_GET_FB("http://www.connect.facebook.com/widgets/serverfbml.php", 'POST', $vAppKey . '&' . $vChannelUrl . '&' . $vFBML);
	$vPostData = 'prefill=true&message=&preview=false&donot_send=false&__d=1&post_form_id=' . $vPostFormID . '&fb_dtsg=' . $vDTGS;
	$vPostData .= '&post_form_id_source=AsyncRequest&lsd=';
	preg_match_all('/PlatformInvite.sendInvitation.*(\&#123.*.?125;)[(\(;)]/im', $vHTML, $vDatas);
	preg_match_all('/<form[^>].*content=\s*["]([^"]+)[^>]*>/im', $vHTML, $vForms);
	preg_match_all('/<form[^>].*action=\s*["]([^"]+)[^>]*>/im', $vHTML, $vURLs);
	$vPostData .= '&' . str_replace('&prefill=&', '&', str_replace(array('&quot;', '&#123;', '&#125;', 'request_form', ':', ','), array('', '', '', 'form_id', '=', '&'), $vDatas[1][0]));
	$vPostData .= '&content=' . urlencode($vForms[1][0]);
	$vCnt = 0;
	foreach ($vFBIDs as $vFBID) {
		$vPostData .= '&to_ids[' . $vCnt . ']=' . $vFBID;
		$vCnt++;
	}
	$vURL = "http://apps.facebook.com/fbml/ajax/prompt_send.php?__a=1";
	proxy_GET_FB($vURL, 'POST', $vPostData);
	$vStart = strpos($vPostData, '&form_id=') + strlen('&form_id=');
	$vStop = strpos($vPostData, '&', $vStart);
	$vPostData2 = 'cmfs_typeahead_' . substr($vPostData, $vStart, $vStop - $vStart) . '=start';
	foreach ($vFBIDs as $vFBID) $vPostData2 .= '&ids%5B%5D=' . $vFBID;
	$vURL = html_entity_decode($vURLs[1][0]);
	proxy_GET_FB($vURL, 'POST', $vPostData2);
}

function pluginload() {
	// get list of plugins
	global $plugins, $userId, $flashRevision, $botlitever, $vWorldType;
	$plugins = array();
	$argv = @$GLOBALS['argv'];
	if (strlen($userId) == 0 && strlen(@$argv[2]) > 0) $userId = @$argv[2];
	if (strlen((@file_get_contents(F('worldtype.txt')))) == 0) $vWorldtype = 'farm';
	$dir = 'plugins/';
	$dh = opendir($dir);
	while (false !== ($file = readdir($dh))) if (is_dir($dir . $file) && $file != '.' && $file != '..') $plugins[] = array('name' => $file, 'folder' => $dir . $file, 'main' => file_exists($dir . $file . '/main.php') ? $dir . $file . '/main.php' : '', 'hooks' => array());
	closedir($dh);
	global $hooks, $this_plugin;
	// initialize plugins
	foreach ($plugins as $key => $plugin) {
		if ($plugin['main']) {
			// load plugin
			include($plugin['main']);
			// find init function
			$init_function = $plugin['name'] . '_init';
			if (function_exists($init_function)) {
				$hooks = array();
				$this_plugin = $plugin;
				// call init function
				call_user_func($init_function);
				if (!(file_exists('notrun_plugin_' . $plugin['name'] . '.txt') || file_exists('notrun_plugin_' . $plugin['name'] . '_' . $userId . '.txt'))) $plugins[$key]['hooks'] = $hooks;
			}
		}
	}
	if (PX_VER_PARSER != PX_VER_SETTINGS) echo "\r\n******\r\nERROR: PX's updated parser version (" . PX_VER_PARSER . ") doesn't match settings version (" . PX_VER_SETTINGS . ")\r\n******\r\n";
}

include_once(AMFPHP_BASE . "amf/io/AMFDeserializer.php");
include_once(AMFPHP_BASE . "amf/io/AMFSerializer.php");
$argv = @$GLOBALS['argv'];
$cmd = @$argv[1];
if ($plugin_developer) pluginload();
// execute command
// echo("Command: ".$cmd."\r\n");
switch ($cmd) {
	case 'get_unit_list_lite':
		global $botlitever;
		$botlitever = 1;
	case 'get_unit_list':
		echo "##### Loading units.txt #####\r\n";
		$work_timer_start = time();
		GetUnitList();
		Parser_Get_Locale();
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
}
?>
