<?php
class fvNeighbors {
    var $userId, $flashRevision, $_token, $_sequence, $_flashSessionKey;
    var $xp, $energy, $error, $haveWorld, $settings, $neighborActs, $helpLimits;
    var $botspeed, $fvdebug, $neighbors, $pneighbors, $_DB, $zErrCGen;
    private function RefreshWorld() {
		////$this->DoInit();
        $this->haveWorld = false;
        $amf = CreateRequestAMF('', 'WorldService.loadWorld');
        $amf->_bodys[0]->_value[1][0]['params'][0] = $this->userId;
        $amf2 = $this->fvSendAMF($amf);
        if ($amf2 === false) {
            $this->error = "fvNeighbors was unable to load the Farmville world";
            AddLog2($this->error);
            return;
        }
        if (@$amf2->_bodys[0]->_value['data'][0]['errorType'] == 0 && @$amf2->_bodys[0]->_value['data'][0]['errorType'] == 0) {
            $this->haveWorld = true;
            $player = $amf2->_bodys[0]->_value['data'][0]['data']['user']['player'];
            $this->settings['level'] = $player['level'];
			/*if ($this->xp >= $this->settings['higherLevelXp']) {
			  $this->settings['level'] = $this->settings['higherLevelBegin'] + floor(($this->xp - $this->settings['higherLevelXp']) / $this->settings['higherLevelStep']);
			}*/
            $this->myneighbors = $player['neighbors'];
            $this->neighborActs = @$player['neighborActionQueue']['m_actionQueue'];
            date_default_timezone_set('UTC');
            $this->helpLimits = @$player['neighborActionLimits']['m_neighborActionLimits'][date('ymd')];
            $this->settings['gold'] = round($player['gold']);
            $this->settings['coin'] = round($player['cash']);
            $this->settings['wsizeX'] = (int)@$amf2->_bodys[0]->_value['data'][0]['data']['world']['sizeX'];
            $this->settings['wsizeY'] = (int)@$amf2->_bodys[0]->_value['data'][0]['data']['world']['sizeY'];
            $this->settings['tileset'] = @$amf2->_bodys[0]->_value['data'][0]['data']['world']['tileSet'];
            $this->settings['wither'] = $player['witherOn'];
            $this->settings['uname'] = $amf2->_bodys[0]->_value['data'][0]['data']['user']['attr']['name'];
            $this->settings['relVersion'] = $player['_explicitType'];
            $this->settings['fmCraft'] = @$amf2->_bodys[0]->_value['data'][0]['data']['user']['player']['storageData']['-7'];
            $this->neighbors = $player['neighbors'];
            $this->pneighbors = $player['pendingNeighbors'];
            $bt_path = getcwd();
            $timezonefile = "$bt_path\\timezone.txt";
            if (file_exists($timezonefile)) {
                $timezone = trim(file_get_contents($timezonefile));
                if (strlen($timezone) > 2) {
                    date_default_timezone_set($timezone);
                }
            }

        }
    }
    private function TableExists($table) {
        $q = $this->_DB->query("SELECT name FROM sqlite_master WHERE type = 'table' and name = '$table'");
        return $q->numRows() == 1;
    }
    //Creates database tables if they need to be, also adds default settings if creating the settings table
    private function CheckDB() {
        if (!empty($this->error)) {
            AddLog2($this->error);
            return;
        }
        if (!$this->TableExists("settings")) {
            AddLog2('Creating Settings Table');
            $fvSQL = 'CREATE TABLE
            settings (
            name CHAR(250) PRIMARY KEY,
            value TEXT )';
            AddLog2("Creating Settings Table " . ($this->_DB->queryExec($fvSQL) ? "Succeded" : "Failed"));
            $this->settings['userid'] = $this->userId;
            $this->settings['level'] = $this->level;
            $this->settings['helpcycle'] = 10;
            $this->settings['helptime'] = 1;
            $this->settings['speed'] = 2;
            $this->settings['version'] = fvNeighbors_version;
            $this->settings['domissions'] = 1;
            $this->settings['dotricks'] = 0;
            $this->settings['pplots'] = 1;
            $this->settings['hanimals'] = 1;
            $this->settings['htrees'] = 1;
            $this->settings['fplots'] = 1;
            $this->settings['accepthelp'] = 1;
            $this->SaveSettings();
        }
        if (!$this->TableExists("neighbors")) {
            AddLog2('Creating Neighbors Table');
            $fvSQL = 'CREATE TABLE
            neighbors (
            fbid CHAR(50) PRIMARY KEY,
            timestamp NUMERIC DEFAULT 0,
            name CHAR(250),
            worldn CHAR(250),
            lastseen NUMERIC,
            level INTEGER DEFAULT 0,
            xp INTEGER DEFAULT 0,
            coin INTEGER DEFAULT 0,
            cash INTEGER DEFAULT 0,
            sizeX INTEGER DEFAULT 0,
            sizeY INTEGER DEFAULT 0,
            fuel INTEGER DEFAULT 0,
            friends INTEGER DEFAULT 0,
            objects INTEGER DEFAULT 0,
            plots INTEGER DEFAULT 0,
            _delete INTEGER DEFAULT 0 )';
            AddLog2("Creating Neighbors Table " . ($this->_DB->queryExec($fvSQL) ? "Succeded" : "Failed"));
            $this->_DB->queryExec('CREATE INDEX name ON neighbors(name)');
        }
        if (!$this->TableExists("neighborsn")) {
            AddLog2('Creating Neighbors Neighbors Table');
            $fvSQL = 'CREATE TABLE
            neighborsn (
            fbid CHAR(50) PRIMARY KEY,
            lastseen NUMERIC DEFAULT 0,
            timestamp NUMERIC DEFAULT 0 )';
            AddLog2("Creating Neighbors Neighbors Table " . ($this->_DB->queryExec($fvSQL) ? "Succeded" : "Failed"));
        }
////
        $q = $this->_DB2->query('SELECT * FROM neighborsname LIMIT 1');
        if ($q === false) {
            AddLog2('Creating Neighbors Name Table');
            $fvSQL = 'CREATE TABLE
              neighborsname (
                neighborid CHAR(25) PRIMARY KEY,
                fullname CHAR(50),
                profilepic TEXT )';
            AddLog2("Creating Neighbors Name Table " . ($this->_DB2->queryExec($fvSQL) ? "Succeded" : "Failed"));
        }
////

    }
    //Function fvNeighbors class initializer
    function fvNeighbors($inittype = '') {
        list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
        $this->userId = $userId;
        $this->_token = $token;
        $this->_sequence = $sequence;
        $this->flashRevision = $flashRevision;
        $this->_flashSessionKey = $flashSessionKey;
        $this->xp = $xp;
        $this->energy = $energy;
        $this->error = '';
        $this->haveWorld = false;
        $this->fndebug = false;
        if (!is_numeric($this->userId)) {
            $this->error = "Farmville Bot Not Initialized/User Unknown";
            return;
        }
        //Open Databases
////
        $this->_DB = new SQLiteDatabase(fvNeighbors_Path . PluginF(fvNeighbors_Main));
        $this->_DB2 = new SQLiteDatabase(fvNeighbors_Path . PluginF(fvNeighbors_name));
        if (!$this->_DB || !$this->_DB2) {
            $this->error = 'fvNeighbors - Database Error';
            return;
        }
        $this->CheckDB(); //Database doesn't exist, create
////
        //Get Settings
        $this->settings = $this->LoadSettings();
        if ($this->settings !== false && (!isset($this->settings['version']) || $this->settings['version'] != fvNeighbors_version)) {
            if ($this->TableExists('neighborsn')) $q = $this->_DB->query("DROP TABLE neighborsn;");
            $this->CheckDB(); //Database doesn't exist, create
            $this->settings['version'] = fvNeighbors_version;
            $this->SaveSettings();
        }
        if ($this->settings === false) {
            $this->CheckDB(); //Database doesn't exist, create
            $this->SaveSettings(); //Insert initial settings

        }
        if ($inittype == 'formload') return;
        //Load the world from Z*
        $this->RefreshWorld();
        if ($this->haveWorld === true) {
            $this->SaveSettings(); //Update the settings
            $this->ProcessEverything(); //Update the World

        }
    }
    function LoadSettings() {
        $q = $this->_DB->query('SELECT * FROM settings');
        if ($q !== false) {
            $results = $q->fetchAll(SQLITE_ASSOC);
            foreach($results as $item) {
                $newresults[$item['name']] = unserialize($item['value']);
            }
            return $newresults;
        }
        return false;
    }
////
    function fvnGetNeighborRealName($uid) {
        $vSQL = "SELECT fullname FROM neighborsname WHERE neighborid='" . $uid . "' LIMIT 1";
        $q = $this->_DB2->query($vSQL);
        $fresult = $q->fetchSingle(SQLITE_ASSOC);
        return ($fresult === false ? false : $fresult);
    }
////
    function SaveSettings() {
        if ($this->TableExists("settings")) $this->CheckDB();
        foreach($this->settings as $key => $value) {
            $insert = "INSERT OR REPLACE INTO settings(name, value) values('%s','%s');";
            $sql = sprintf($insert, $key, serialize($value));
            $this->_DB->queryExec($sql);
        }
    }
    //This process everything to do the with the farm, accepts help, deletes neighbors, and traverses neighbors, etc..
    private function ProcessEverything() {
        if ($this->TableExists("neighbors")) {
            //Insert Missing Neighbors
            foreach($this->neighbors as $neigh) {
                @$this->_DB->queryExec("INSERT OR IGNORE INTO neighbors(fbid) VALUES('$neigh')");
            }
            //Delete Neighbors
            $q = $this->_DB->query("SELECT fbid FROM neighbors WHERE _delete=1")->fetchAll(SQLITE_ASSOC);
            foreach($q as $result) {
                $this->RemoveNeighbor($result['fbid']);
            }
////
            $this->MyFvFunction();
////

        }
        //Cancel Pending Neighbor Requests
        if (@$this->settings['delpending'] == 1) {
            $cnt = 0;
            AddLog2('Pending Count: ' . count($this->pneighbors));
            foreach($this->pneighbors as $pendn) {
                $this->CancelNeighbor($pendn);
                $cnt++;
                if ($cnt == $this->settings['helpcycle']) break;
            }
        }
        //Clear Neighbor Actions
        if (@$this->settings['accepthelp'] == 1) $this->DoNeighborHelp();
        $iguser = load_array('ignores.txt');
        //Update Neighbors
        if ($this->TableExists("neighbors")) {
            $cfgtime = time() - (3600 * $this->settings['helptime']);
            $fvSQL = "SELECT * FROM neighbors WHERE timestamp <= '$cfgtime' LIMIT " . $this->settings['helpcycle'];
            $results = $this->_DB->query($fvSQL)->fetchAll(SQLITE_ASSOC);
            AddLog2(sprintf('Updating %d Neighbors', count($results)));
            foreach($results as $result) {
                if (isset($iguser[$result['fbid']])) continue;
                $this->UpdateNeighbor($result, 'neighbor');
            }
            AddLog2('Finished Updating Neighbors');
        }
        //Update Neighbor Neighbors
        if ($this->settings['vneighborsn'] == 1 && $this->TableExists("neighborsn")) {
            $cfgtime = time() - (3600 * $this->settings['helptime']);
            $FifteenDays = time() - (3600 * 24 * 15);
            $fvSQL = "SELECT * FROM neighborsn WHERE timestamp <= '$cfgtime' AND (lastseen >= '$FifteenDays' OR lastseen = 0) LIMIT " . $this->settings['helpcycle'];
            @$results = $this->_DB->query($fvSQL)->fetchAll(SQLITE_ASSOC);
            AddLog2(sprintf('Updating %d Neighbors Neighbors', count($results)));
            foreach($results as $result) {
                if (isset($iguser[$result['fbid']])) continue;
                $this->UpdateNeighbor($result, 'NN');
            }
            AddLog2('Finished Updating Neighbors Neighbors');
        }
    }
    //Process and accepts everyone's help on your own farm
    function DoNeighborHelp() {
        AddLog2('Accepting ' . count($this->neighborActs) . ' Neighbors Help');
        $amfcount = 0;
        $amf = '';
        $tmp = array();
        $this->LoadBotSettings();
        $total = 0;
        foreach($this->neighborActs as $nActs) $total+= count($nActs['actions']);
        foreach($this->neighborActs as $nActs) {
            $nid = $nActs['visitorId'];
            foreach($nActs['actions'] as $acts) {
                $amf = $this->CreateMultAMFRequest($amf, $amfcount, '', 'NeighborActionService.clearNeighborAction');
                $amf->_bodys[0]->_value[1][$amfcount]['params'][0] = $nid;
                $amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $acts['actionType'];
                $amf->_bodys[0]->_value[1][$amfcount]['params'][2] = $acts['objectId'];
                $tmp[$amfcount]['id'] = $nid;
                $tmp[$amfcount]['action'] = $acts['actionType'];
                if ($amfcount < $this->botspeed - 1 && ($total-- > 1)) {
                    $amfcount++;
                    continue;
                }
                $amf2 = $this->SendAMF($amf);
                $amf = '';
                $amfcount = 0;
                if ($amf2 === false) continue;
                foreach($amf2->_bodys[0]->_value['data'] as $key => $returned) {
                    if ($returned['errorType'] == 0) {
                        AddLog2(sprintf('[%s] Action: %s - %s - Result: %s', $key, $tmp[$key]['action'], $tmp[$key]['id'], $this->GetErrorDesc($returned['errorType'])));
                    }
                }
            }
        }
    }
    //Attempts to visit a neighbor, errors if it cant download the farm, else it process the farm
    function UpdateNeighbor($result, $type) {
        $amf = '';
        $amf = $this->CreateMultAMFRequest($amf, 0, '', 'WorldService.loadWorld');
        $amf->_bodys[0]->_value[1][0]['params'][0] = $result['fbid'];
        $response = $this->SendAMF($amf);
        if ($response === false) return;
        $this->_DB->queryExec("BEGIN;");
        foreach($response->_bodys[0]->_value['data'] as $key => $value) {
            if ($value['errorType'] == 0) $this->ProcessFriend($value, $type);
        }
        $this->_DB->queryExec("COMMIT;");
    }
    function GetNeighbors() {
        $q = $this->_DB->query('SELECT * FROM neighbors WHERE _delete=0 ORDER BY name');
        if ($q === false) return array();
        else return $q->fetchAll(SQLITE_ASSOC);
    }
    function DoSettings() {
        $this->settings['accepthelp'] = (isset($_GET['accepthelp']) ? 1 : 0);
        $this->settings['delpending'] = (isset($_GET['delpending']) ? 1 : 0);
        $this->settings['htrees'] = (isset($_GET['htrees']) ? 1 : 0);
        $this->settings['hanimals'] = (isset($_GET['hanimals']) ? 1 : 0);
        $this->settings['pplots'] = (isset($_GET['pplots']) ? 1 : 0);
        $this->settings['fplots'] = (isset($_GET['fplots']) ? 1 : 0);
        $this->settings['ucrops'] = (isset($_GET['ucrops']) ? 1 : 0);
        $this->settings['fchickens'] = (isset($_GET['fchickens']) ? 1 : 0);
        $this->settings['getcandy'] = (isset($_GET['getcandy']) ? 1 : 0);
        $this->settings['vneighborsn'] = (isset($_GET['vneighborsn']) ? 1 : 0);
        $this->settings['domissions'] = (isset($_GET['domissions']) ? 1 : 0);
        $this->settings['dotricks'] = (isset($_GET['dotricks']) ? 1 : 0);
        $this->settings['sendfeed'] = (isset($_GET['sendfeed']) ? 1 : 0);
        $this->settings['getholidaygifts'] = (isset($_GET['getholidaygifts']) ? 1 : 0);
        $this->settings['wworkshop'] = (isset($_GET['wworkshop']) ? 1 : 0);
        $this->settings['hgreenhouse'] = (isset($_GET['hgreenhouse']) ? 1 : 0);
		$this->settings['hcottage'] = (isset($_GET['hcottage']) ? 1 : 0);
        $this->settings['fpigpen'] = (isset($_GET['fpigpen']) ? 1 : 0);
        $this->settings['helpcycle'] = (isset($_GET['helpcycle']) && is_numeric($_GET['helpcycle']) ? $_GET['helpcycle'] : 0);
        $this->settings['helptime'] = (isset($_GET['helptime']) && is_numeric($_GET['helptime']) ? $_GET['helptime'] : 0);
        $this->SaveSettings();
    }
    private function SendAMF($amf) {
        $answer = RequestAMFIntern($amf);
        $doinit = 0;
        if (!isset($answer->_bodys[0]->_value['data'][0])) {
            $this->DoInit();
            return false;
        }
        foreach(@$answer->_bodys[0]->_value['data'] as $key => $returned) {
            $resp = @$returned['errorType'];
            $err = @$returned['errorData'];
            if ($resp == 28 || $resp == 29) {
                if (strpos($err, 'MC::lock()') !== false) {
                    //Ignore Quietly Now, even if Debug is on
                    $iguser = load_array('ignores.txt');
                    preg_match('/rts_USER_(.*)_lock/', $err, $matches);
                    $iguser[floatval($matches[1]) ] = floatval($matches[1]);
                    save_array($iguser, 'ignores.txt');
                } elseif (strpos($err, 'Exceeded action limit') !== false) {
                } elseif (strpos($err, 'Invalid action') !== false) {
                    //Ignore Quietly Now, even if Debug is on
                } else {
                    if ($err != 'Remaining function') AddLog2(sprintf('fvNeighbors Error: %s - %s', $resp, $err));
                }
                //unset($answer->_bodys[0]->_value['data'][$key]);
                if ($doinit == 0) $this->DoInit();
                $doinit = 1;
            }
        }
        return $answer;
    }
	
	private function fvSendAMF($amf) {
        $s = Connect();
		$ser = new AMFSerializer();	
		$answer = new AMFObject(Request($s, $ser->serialize($amf)));
		$dser = new AMFDeserializer($answer->rawData);
		$dser->deserialize($answer);
		$doinit = 0;
		if (!isset($answer->_bodys[0]->_value['data'][0])) { 
			$this->DoInit(); 
			return false; 
		}
        return $answer;
    }
	
    private function IsTrickable($obj) {
        switch ($obj['className']) {
            case 'Tree':
                if ($obj['itemName'] == 'clementine') return false;
                if ($obj['itemName'] == 'plum') return false;
                if ($obj['itemName'] == 'cashew') return false;
                return ($obj['usesAltGraphic'] == false && $obj['altGraphic'] != 'web');
            case 'Animal':
                return ($obj['usesAltGraphic'] == false && $obj['altGraphic'] != 'web');
            case 'RotateableDecoration':
                if ($obj['itemName'] == 'fenceegg') return false;
                return ($obj['usesAltGraphic'] == false && $obj['altGraphic'] != 'web' && strpos($obj['itemName'], 'fence') !== false);
            case 'StorageBuilding':
                return ($obj['isFullyBuilt'] == false && strpos($obj['itemName'], 'barn') == 1 && @(!isset($obj['overlayData']['webOverlay'])));
            }
            return false;
        }
        //The bulk of the plugin, actually grabs possible activites to do ont he neighbors famr, does them, and updates the stats.
        private function ProcessFriend($returned, $friendtype) {
			$player = @$returned['data']['user']['player'];
            $fr['laston'] = $returned['data']['user']['lastWorldAction'];
            $fr['fbid'] = $returned['data']['user']['id'];
            $fr['name'] = $this->fvnGetNeighborRealName($fr['fbid']);
			$nbor['name'] = empty($nbor['name']) ? $fr['fbid'] : $nbor['name'];
            $fr['worldn'] = $fr['name'];
            $fr['level'] = 0;
            $fr['exp'] = 0;
            $fr['coin'] = 0;
            $fr['cash'] = 0;
            $fr['fuel'] = 0;
            $fr['sizeX'] = (($returned['data']['world']['sizeX']) - 2) / 4;
            $fr['sizeY'] = (($returned['data']['world']['sizeY']) - 2) / 4;
            $fr['neigh'] = $this->myneighbors;
            $fr['neighActs'] = @$player['neighborActionQueue']['m_actionQueue'][$this->userId]['actions'];
            $fr['neighcnt'] = count($fr['neigh']);
            $fr['objects'] = $returned['data']['world']['objectsArray'];
            $fr['objectscnt'] = count($fr['objects']);
            $fr['plots'] = 0;
            $fr_objects = $returned['data']['world']['objectsArray'];
            $fr_objectscnt = count($fr_objects);
            //Insert NN into DB
            if ($friendtype == 'neighbor') {
                if ($this->TableExists("neighborsn")) {
                    foreach($fr['neigh'] as $NN) {
                        if ($NN != $this->userId && !isset($this->neighbors[$NN])) $this->_DB->queryExec("INSERT OR IGNORE INTO neighborsn(fbid) VALUES ('$NN')");
                    }
                }
            }
            if ($friendtype == 'neighbor') {
                $sql = sprintf("INSERT OR REPLACE INTO neighbors(
                fbid,    name,  worldn,  lastseen,
                level,   xp,    coin,    cash,
                sizeX,   sizeY, fuel,    friends,
                objects, plots, timestamp
              ) VALUES (
                '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s',
                '%s', '%s', '%s'
              );", $fr['fbid'], str_replace('\'','',$fr['name']), str_replace('\'','',$fr['worldn']), $fr['laston'], $fr['level'], $fr['exp'], $fr['coin'], $fr['cash'], $fr['sizeX'], $fr['sizeY'], $fr['fuel'], $fr['neighcnt'], $fr['objectscnt'], $fr['plots'], time());
                $this->_DB->queryExec($sql);
            } else {
                $sql = sprintf("UPDATE neighborsn SET timestamp='%s',  lastseen='%s' WHERE fbid='%s'", time(), $fr['laston'], $fr['fbid']);
                $this->_DB->queryExec($sql);
            }
            $fr['worldn'] = str_replace("''", "'", $fr['worldn']);
            AddLog2("--------------------------------------------------");
            AddLog2(sprintf("%s - %s - Updated", $fr['worldn'], $fr['fbid']));
            $this->settings['ntracktime'] = time();
////
            $fr_fbid = $fr['fbid'];
            if (is_array($this->helpLimits)) {
                foreach($this->helpLimits as $nkey => $ndata) {
                    if ($ndata['targetId'] == $fr_fbid) {
                        $myactions = $ndata;
                        break;
                    }
                }
            }
////
			$farmActs = 0;
			$feedActs = 0;
			$halloweenActs = 0;
			$animalFeedActs = 0;
			$pigslopActs = 0;
			$Val2011BuildActs = 0;
			$greenHouseBuildActs = 0;
			$cottageBuildActs = 0;
			$acts['trickneighbor'] = 0;

			if (!empty($myactions))
			{
				$farmActs = @$myactions['farm'];
				$feedActs = @$myactions['feed'];
				$halloweenActs = @$myactions['harvesthalloweencandy'];
				$animalFeedActs = @$myactions['animalFeed'];
				$pigslopActs = @$myactions['pigslop'];
				$Val2011BuildActs = @$myactions['Valentines2011Harvest'];
				$greenHouseBuildActs = @$myactions['greenhousebuildable_finished'];
				$cottageBuildActs = @$myactions['Stpatty2011Harvest'];
				$acts['trickneighbor'] = @$myactions['trickneighbor'];
			}
			AddLog2(sprintf("Performed  %d/%d Farm Acts,  %d/%d Chickens Fed,  %d/%d Candy Collected, %d/%d Tricks Played,  %d/%d Pigslopsent,  %d/%d Animal Feed Sent", $farmActs, '5', $feedActs, "1", $halloweenActs, "1", $acts['trickneighbor'], "1", $pigslopActs, "1", $animalFeedActs, "1"));
			AddLog2(sprintf("Performed  %d/%d Valentines2011 Harvested,  %d/%d GreenHouse Harvested,  %d/%d Leprechaun Cottage Harvested", $Val2011BuildActs, "1", $greenHouseBuildActs, "1", $cottageBuildActs, "1"));
			AddLog2("--------------------------------------------------");
			
			if ($farmActs < 5 || $feedActs < 1 || $halloweenActs < 1 || $pigslopActs < 1 || $animalFeedActs < 1 || $Val2011BuildActs < 1 || $greenHouseBuildActs < 1 || $cottageBuildActs < 1 || $acts['trickneighbor'] < 1)
			{
				foreach(@$fr_objects as $wObjects) {
                    switch ($wObjects['className']) {
                        case 'Plot':
                            if ($farmActs < 5) {
                                switch ($wObjects['state']) {
                                    case 'withered':
                                        if (@$this->settings['ucrops'] == 1) {
                                            $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'unwither');
                                            $farmActs++;
                                        }
                                    break;
                                    case 'fallow':
                                        if (@$this->settings['pplots'] == 1) {
                                            $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'plow');
                                            $farmActs++;
                                        }
                                    break;
                                    default:
                                        if (@$this->settings['fplots'] == 1 && $wObjects['isJumbo'] === false) {
                                            $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'fert');
                                            $farmActs++;
                                        }
                                }
                            }
                        break;
                        case 'Animal':
                            if ($farmActs < 5) {
                                if ($wObjects['state'] == 'ripe') {
                                    $uinfo = Units_GetUnitByName($wObjects['itemName']);
                                    if (@$uinfo['action'] != 'transform' && @$this->settings['hanimals'] == 1) {
                                        $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'harvest');
                                        $farmActs++;
                                    }
                                }
                            }
                        break;
                        case 'Tree':
                            if ($farmActs < 5) {
                                if ($wObjects['state'] == 'ripe' && @$this->settings['htrees'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'harvest');
                                    $farmActs++;
                                }
                            }
							if($acts['trickneighbor'] < 1 && @$this->settings['dotricks'] == 1 && $this->IsTrickable($wObjects))
							{ 
								$work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'trickneighbor');
								$acts['trickneighbor']++;
							}
						break;
                        case 'ChickenCoopBuilding':
                            if ($feedActs < 1) {
                                if ($wObjects['isFullyBuilt'] === true && @$this->settings['fchickens'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'feedchickens');
                                    $feedActs++;
                                }
                            }
                        break;
                        case 'HalloweenHauntedHouseBuilding':
                            if ($halloweenActs < 1) {
                                if ($wObjects['isFullyBuilt'] === true && @$this->settings['hcandy'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'harvesthalloweencandy');
                                    $halloweenActs++;
                                }
                            }
                        break;
                        case 'FeedTroughBuilding':
                            if ($animalFeedActs < 1) {
                                if ($wObjects['isFullyBuilt'] === true && @$this->settings['sendfeed'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'depositAnimalFeed');
                                    $animalFeedActs++;
                                }
                            }
                        break;
                        case 'PigpenBuilding':
                            if ($pigslopActs < 1) {
                                if ($wObjects['isFullyBuilt'] === true && @$this->settings['fpigpen'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'getVisitPigSlopW2W');
                                    $pigslopActs++;
                                }
                            }
                        break;
                        case 'FeatureBuilding':
                            if ($Val2011BuildActs < 1) {
                                if ($wObjects['itemName'] == 'valentines2011_finished' && @$this->settings['getholidaygifts'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'neighborHarvestFeatureBuilding');
                                    $Val2011BuildActs++;
                                }
                            }
                            if ($greenHouseBuildActs < 1) {
                                if ($wObjects['itemName'] == 'greenhousebuildable_finished' && @$this->settings['hgreenhouse'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'neighborHarvestFeatureBuilding');
                                    $greenHouseBuildActs++;
                                }
                            }
							if ($cottageBuildActs < 1) {
                                if ($wObjects['itemName'] == 'stpatty2011_finished' && @$this->settings['hcottage'] == 1) {
                                    $work[] = array('objectArray' => $wObjects, 'fbid' => $fr_fbid, 'action' => 'neighborHarvestFeatureBuilding');
                                    $cottageBuildActs++;
                                }
                            }
                    }
                    if ($farmActs == 5 && $feedActs == 1 && $halloweenActs == 1 && $animalFeedActs == 1 && $pigslopActs == 1 && $Val2011BuildActs == 1 && $greenHouseBuildActs == 1 && $cottageBuildActs == 1 && $acts['trickneighbor'] == 1) break;
				}
                //Now Submit Work
                $amf = '';
                $tmpArray = array();
                $amfcount = 0;
                if (!empty($work)) {
                    foreach($work as $wk) {
                        if ($wk['action'] != 'depositAnimalFeed' && $wk['action'] != 'getVisitPigSlopW2W') {
                            $amf = $this->CreateMultAMFRequest($amf, $amfcount, 'neighborAct', 'WorldService.performAction');
                            $amf->_bodys[0]->_value[1][$amfcount]['params'][1] = $wk['objectArray'];
                            $amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['actionType'] = $wk['action'];
                            $amf->_bodys[0]->_value[1][$amfcount]['params'][2][0]['hostId'] = $wk['fbid'];
                        } else {
                            $amf = $this->CreateMultAMFRequest($amf, $amfcount, '', 'NeighborActionService.' . $wk['action']);
                            $amf->_bodys[0]->_value[1][$amfcount]['params'][0] = $wk['fbid'];
                        }
                        $tmpArray[$amfcount]['id'] = $wk['fbid'];
                        $tmpArray[$amfcount]['action'] = $wk['action'];
                        $tmpArray[$amfcount]['item'] = $wk['objectArray']['itemName'];
                        if ($amfcount < $this->botspeed - 1) {
                            $amfcount++;
                            continue;
                        }
 						$amf2 = $this->SendAMF($amf);
                        $amf = '';
                        $amfcount = 0;
                        if ($amf2 === false) continue;
						
                        foreach($amf2->_bodys[0]->_value['data'] as $key => $returned) {
                            $resp = $returned['errorType'];
                            $err = $returned['errorData'];
                            $reward = @$returned['data']['rewardItem'];
                            if ($resp == 0) {
                                AddLog2(sprintf("[%d] Action: %s -  %s  - Experience: %s - Coins: %s - Result: %s%s",
								$key, $tmpArray[$key]['action'], $tmpArray[$key]['item'], $returned['data']['xpYield'], 
								$returned['data']['goldYield'], $this->GetErrorDesc($resp),
								($reward != '' ? sprintf(" Reward: %s", Units_GetRealnameByName($reward)) : '')));
                            }
                        }
                    }
                }
                if ($amf != '') //Still have requests left
                {
                    $amf2 = $this->SendAMF($amf);
					if ($amf2 !== false) {
                        foreach($amf2->_bodys[0]->_value['data'] as $key => $returned) {
                            $resp = $returned['errorType'];
                            $err = $returned['errorData'];
                            $reward = @$returned['data']['rewardItem'];
                            if ($resp == 0) {
                                AddLog2(sprintf("[%d] Action: %s -  %s  - Experience: %s - Coins: %s - Result: %s%s",
								$key, $tmpArray[$key]['action'], $tmpArray[$key]['item'], $returned['data']['xpYield'], 
								$returned['data']['goldYield'], $this->GetErrorDesc($resp),
								($reward != '' ? sprintf(" Reward: %s", Units_GetRealnameByName($reward)) : '')));
                            }
                        }
                    }
                }
            }
////
            //Get A Random Mission and do it
            if ($this->settings['domissions'] == 1) {
                $found = false;
                $amf = CreateRequestAMF('', 'MissionService.getRandomMission');
				$amf->_bodys[0]->_value[1][0]['params'][0] = $fr['fbid'];
                $response = $this->SendAMF($amf);
                if ($response !== false && count($response->_bodys[0]->_value['data']) > 0) {
                    foreach($response->_bodys[0]->_value['data'] as $key => $returned) {
                        $resp = $returned['errorType'];
                        $err = $returned['errorData'];
                        if ($resp == 0 && isset($returned['data']['type'])) {
                            $found = true;
                            $amf = CreateRequestAMF('', 'MissionService.completeMission');
							$amf->_bodys[0]->_value[1][0]['params'][0] = $fr['fbid'];
                            $amf->_bodys[0]->_value[1][0]['params'][1] = $returned['data']['type'];
                            $amf2 = $this->SendAMF($amf);
                            $mission = $returned['data']['type'];
                            foreach($amf2->_bodys[0]->_value['data'] as $key => $returned) {
                                AddLog2(sprintf("Do Mission: %s - Result: %s", ucfirst($mission), $this->GetErrorDesc($resp)));
								AddLog2("--------------------------------------------------");
                            }
                        }
                    }
                }
            }
        }
        private function CreateMultAMFRequest($amf, $cnt, $req = '', $func) {
            if ($cnt == 0) {
                $amf = new AMFObject("");
                $amf->_bodys[0] = new MessageBody();
                $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
                $amf->_bodys[0]->responseURI = '/1/onStatus';
                $amf->_bodys[0]->responseIndex = '/1';
                $amf->_bodys[0]->_value[0] = GetAMFHeaders();
                $amf->_bodys[0]->_value[2] = 0;
            }
            $amf->_bodys[0]->_value[1][$cnt]['sequence'] = GetSequense();
            $amf->_bodys[0]->_value[1][$cnt]['params'] = array();
            if ($req) $amf->_bodys[0]->_value[1][$cnt]['params'][0] = $req;
            if ($func) $amf->_bodys[0]->_value[1][$cnt]['functionName'] = $func;
            return $amf;
        }
        private function LoadBotSettings() {
            //Get Settings From Bot
            if (file_exists(F('settings.txt'))) {
                $settings_list = @explode(';', trim(file_get_contents(F('settings.txt'))));
                foreach($settings_list as $setting_option) {
                    $set_name = @explode(':', $setting_option);
                    if (count($set_name) > 2) {
                        $liststart = explode(':', $setting_option, 3);
                        $listopt = explode(':', $liststart[2]);
                        $tired = count($listopt);
                        for ($i = 0;$i < $tired;$i = $i + 2) {
                            $bot_settings[$liststart[0]][$listopt[$i]] = $listopt[$i + 1];
                        }
                    } else {
                        $bot_settings[$set_name[0]] = $set_name[1];
                    }
                }
                $this->botspeed = ($bot_settings['bot_speed'] < 1) ? 1 : $bot_settings['bot_speed'];
            }
        }
        private function GetErrorDesc($code) {
            switch ($code) {
                case 0:
                    return 'OK';
                case 1:
                    return 'Error - Authorization';
                case 2:
                    return 'Error - User Data Missing';
                case 3:
                    return 'Error - Invalid State';
                case 4:
                    return 'Error - Invalid Data';
                case 5:
                    return 'Error - Missing Data';
                case 6:
                    return 'Error - Action Class Error';
                case 7:
                    return 'Error - Action Method Error';
                case 8:
                    return 'Error - Resource Data Missing';
                case 9:
                    return 'Error - Not Enough Money';
                case 10:
                    return 'Error - Outdated Game Version';
                case 25:
                    return 'Error - General Transport Failure';
                case 26:
                    return 'Error - No User ID';
                case 27:
                    return 'Error - No Session';
                case 28:
                    return 'Retry Transaction';
                case 29:
                    return 'Force Reload';
                default:
                    return sprintf("Error: Unknown %d", $code);
            }
        }
        private function DoInit() {
            // Create Init request
			list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
			SetSequense(0);

			$amf = CreateRequestAMF('', 'UserService.initUser');
			$amf->_bodys[0]->_value[1][0]['params'][0] = "";
			$amf->_bodys[0]->_value[1][0]['params'][1] = -1;
			$amf->_bodys[0]->_value[1][0]['params'][2] = true;

			$amf2=RequestAMFIntern($amf);
			$res=CheckAMF2Response($amf2);
        }
        function RemoveNeighbor($neighborid) {
            $response = $this->SendWebCommand('action=removeNeighbor&uid=' . $neighborid);
            if ($response == null) {
                AddLog2("fvNeighbors: Recieved no Answer Cancel Deleting FBID:" . $neighborid);
            } else if (strpos($response, 'HTTP/1.1 200 OK') !== false) {
                $q = $this->_DB->query("DELETE FROM neighbors WHERE fbid='$neighborid'", $error);
                AddLog2("fvNeighbors - Action: Delete - NeighborID: $neighborid - OK");
            }
        }
        function CancelNeighbor($neighborid) {
            $response = $this->SendWebCommand('action=cancelRequest&uid=' . $neighborid);
            if ($response == null) {
                AddLog2("fvNeighbors: Recieved no Answer Cancel Request FBID:" . $neighborid);
            } else if (strpos($response, 'HTTP/1.1 200 OK') !== false) {
                $q = $this->_DB->query("DELETE FROM neighbors WHERE fbid='$neighborid'", $error);
                AddLog2("fvNeighbors - Action: CancelRequest - NeighborID: $neighborid - OK");
            }
        }
        //This should probably be changed to use the Request() fuynction in parser.php, but I need to fully understand it first.
        function SendWebCommand($content) {
            $vURL='http://apps.facebook.com/onthefarm/neighbors.php?zyUid=' . $this->userId . '&zySnuid=' . $this->userId . '&zySnid=1&zySig=' . $this->_token;
            $response=proxy_GET_FB($url, 'POST', $content);
            return $response;
        }
        function DeleteNeigh($fbid) {
            return $this->_DB->queryExec("UPDATE neighbors SET _delete=1 WHERE fbid='$fbid';", $error);
        }
        function NNCount() {
            if (!$this->TableExists("neighborsn")) return 0;
            $result = $this->_DB->query("SELECT count(*) as ncnt FROM neighborsn")->fetchAll(SQLITE_ASSOC);
            return $results[0]['ncnt'];
        }
////
        
		function MyFvFunction() {
			$temp2 = proxy_GET_FB('http://apps.facebook.com/onthefarm/neighbors.php');
			unset($temp2);
            $temp = file_get_contents(F('flashVars.txt'));
            preg_match('/var g_friendData = \[([^]]*)\]/sim', $temp, $friend);
            unset($temp);
            if (!isset($friend[1])) return;
            preg_match_all('/\{([^}]*)\}/sim', $friend[1], $friend2);
            unset($friend);
            foreach($friend2[1] as $f) {
                preg_match_all('/"([^"]*)":"([^"]*)"/im', $f, $frz);
                $newarray[] = array_combine($frz[1], $frz[2]);
            }
            unset($friend2, $frz);
            $uSQL = '';
            foreach($newarray as $friends) {
                if ($friends['is_app_user'] != 1) continue;
                $friends['pic_square'] = str_replace('\\/', '\\', $friends['pic_square']);
                $friends['name'] = str_replace("'", "''", $friends['name']);
                $friends['name'] = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $friends['name']);
                $uSQL.= "INSERT OR REPLACE INTO neighborsname(neighborid, fullname, profilepic) values('" . $friends['uid'] . "',
                '" . $friends['name'] . "', '" . $friends['pic_square'] . "');";
            }
            $this->_DB2->queryExec($uSQL);
            unset($uSQL, $newarray);
        }
		
////
    }
?>
