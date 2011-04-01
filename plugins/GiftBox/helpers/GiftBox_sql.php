<?php
//*********************************
//  Open the user database
//*********************************
function GBDBuser_init($origin) {
    AddLog2('GB DB user: init');
    global $GBDBuser;
    global $GBDBmain;
    list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
    if ($userId == "") {
        AddLog2("GB fail (userId unknown");
        return "fail";
    }
    $GBDBuser = sqlite_open("plugins/GiftBox/" . $userId . "_" . GBox_DB_user) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    if (!$GBDBuser) {
        AddLog2('GB DB Error: user init' . $err);
        return "fail";
    }
    sqlite_query($GBDBuser, 'PRAGMA cache_size=200000');
    sqlite_query($GBDBuser, 'PRAGMA synchronous=OFF');
    sqlite_query($GBDBuser, 'PRAGMA count_changes=OFF');
    sqlite_query($GBDBuser, 'PRAGMA journal_mode=MEMORY');
    sqlite_query($GBDBuser, 'PRAGMA temp_store=MEMORY');
    // check if the tables exist or need to be created.
    $GBSQL = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'giftbox'";
    $result = sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("Tables need to be created. ");
    if (sqlite_num_rows($result) == 0) {
        GBDBuser_create($userId);
    }
    // See if we need to run database routine
    if ($origin == "Hook") {
        $now = time();
        $last = time() - 3000;
        $lastUpdate = GB_Get_User_Setting('LastUpdate');
        $diff = $now - $lastUpdate;
        //AddLog2('GB database routine run : ' . $diff . ' Sec. ago ' );
        AddLog2('GB database routine run : ' . nicetime($lastUpdate));
        if ($lastUpdate < $last || $lastUpdate == 'Fail' || $lastUpdate == "Not Found") {
            AddLog2('GB Need to run database routines (every 30 min).');
            AddLog2('GB DB cleaning.');
            sqlite_query($GBDBuser, "vacuum") or GB_AddLog("*** SQL Error *** ");
            sqlite_query($GBDBmain, "vacuum") or GB_AddLog("*** SQL Error *** ");
            sqlite_query($GBDBuser, "BEGIN TRANSACTION");
            sqlite_query($GBDBmain, "BEGIN TRANSACTION");
            //AddLog2('GB Check image version.');
            //GB_image_Settings();
            // Check if flash version is up2date
            $result1 = sqlite_query($GBDBmain, "SELECT _val FROM gamesettings WHERE _set = 'flashversion' limit 1");
            if (sqlite_num_rows($result1) > 0) {
                $flashversion = sqlite_fetch_single($result1);
            } else {
                $flashversion = "'NULL'";
            }
            $flashNeedUpdate = "N";
            if ($flashversion == $flashRevision) {
                AddLog2('GB DB main: Flash version up to date. (' . $flashversion . ")");
            } else {
                AddLog2('GB DB main: Flash version needs update now. have: ' . $flashversion . " need: " . $flashRevision);
                $flashNeedUpdate = "Y";
            }
            if ($flashNeedUpdate == "Y") {
                AddLog2('GB DB main: Updating units.');
                GB_gameSettings_SQL($flashRevision);
                GB_SQL_updSetting($GBDBuser, 'gamesettings', 'flashversion', $flashRevision);
                GB_Update_User_Setting('flashRevision', $flashRevision);
            }
            AddLog2('GB DB user: Detecting Special items.');
            GB_DetectSpecials2();
            AddLog2('GB DB user: Detecting building parts.');
            GB_DetectBuildingParts4();
            AddLog2('GB DB user: Detecting collection items.');
            GB_DetectCollections();
            GB_Update_User_Setting('LastUpdate', $now);
            sqlite_query($GBDBuser, "COMMIT TRANSACTION");
            sqlite_query($GBDBmain, "COMMIT TRANSACTION");
        } // 10 min. check

    } //not hook

}
//*********************************
//  Create the user database
//*********************************
function GBDBuser_create($userId) {
    global $GBDBuser;
    global $GBDBmain;
    sqlite_query($GBDBuser, 'CREATE TABLE giftbox (
                id INT PRIMARY KEY,   _itemcode CHAR(5) unique,
                _amount CHAR(5),  _orig CHAR(5),      _gifters CHAR(250) )');
    sqlite_query($GBDBuser, 'CREATE TABLE totstorage (
                id INT PRIMARY KEY,   _storagecode CHAR(5),  _itemcode CHAR(5),
                _amount CHAR(5),      _gifters CHAR(250) )');
    sqlite_query($GBDBuser, 'CREATE TABLE locations (
                id INT PRIMARY KEY,   _X INT,  _Y INT,
                _what CHAR(25) )');
    sqlite_query($GBDBuser, 'CREATE INDEX "locYX" ON "locations" ("_X", "_Y")');
    GB_AddLog("GB user settings not found. will add defaults now. ");
    sqlite_query($GBDBuser, 'CREATE TABLE gamesettings (
                _id INT PRIMARY KEY,    _set CHAR(10) unique, _val CHAR(10)); ');
    // setting the defaults
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('RunPlugin','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoFuel','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoSpecials','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoSelling','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoPlace','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoFeetPet','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoColl','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoCollSell','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoCollTrade','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoCollKeep','5')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoConstr','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoPlaceBuild','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('ShowImage','1')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('ShowImageAll','0')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoDebug','0')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DoResetXML','0')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('AnimalX1','35')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('AnimalY1','0')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('AnimalX2','45')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('AnimalY2','65')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('TreeX1','55')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('TreeY1','0')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('TreeX2','65')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('TreeY2','65')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DecorationX1','45')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DecorationY1','0')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DecorationX2','55')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('DecorationY2','65')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('flashversion','" . $flashRevision . "')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    // now load the settings from general_settings.txt
    $GB_Sets = file('plugins/GiftBox/general_settings.txt');
    if ($GB_Sets) {
        GB_AddLog('Default settings file found.');
        foreach($GB_Sets as $GB_Set) {
            $GB_TSet = explode(':', $GB_Set);
            if (strpos($GB_TSet['0'], '#') !== false) {
                $comment = $GB_TSet['0'];
            } else {
                $GB_settVar = $GB_TSet['0'];
                $GB_settVal = $GB_TSet['1'];
                $GBSQL = "INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES('" . $GB_settVar . "','" . $GB_settVal . "')";
                //echo 'SQL: ' . $GBSQL ;
                sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
            }
        }
    }
    //Default loaded, now add the rest.
    $GBSQL = "INSERT INTO gamesettings(_set,_val) VALUES('userid','" . $userId . "')";
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);

    sqlite_query($GBDBuser, 'CREATE TABLE objects (
                _id INT PRIMARY KEY,    _obj CHAR(5),  _set CHAR(25),  _val CHAR(100)); ');
    sqlite_query($GBDBuser, 'CREATE INDEX "obj_obj" ON "objects" ("_obj")');
    sqlite_query($GBDBuser, 'CREATE INDEX "obj_SV" ON "objects" ("_set", "_val")');
    sqlite_query($GBDBuser, 'CREATE TABLE stats (
                _id INT PRIMARY KEY,    _code CHAR(5),    _number INT,  _action CHAR(25),  _name CHAR(25),  _date datetime); ');
    sqlite_query($GBDBuser, 'CREATE TABLE action (
                _code CHAR(10) PRIMARY KEY,                _place_on_farm CHAR(10) DEFAULT 0 ,
                _place_in_build CHAR(10) DEFAULT 0 ,       _place_in_amount CHAR(10) DEFAULT 0 ,
                _place_in_max CHAR(10) DEFAULT 0 ,         _place_in_special CHAR(10) DEFAULT 0 ,
                _target CHAR(10) DEFAULT 0 ,               _selling CHAR(10) DEFAULT 0 ,
                _keep CHAR(10) DEFAULT 0 ,                 _collection CHAR(10) DEFAULT 0 ,
                _consume CHAR(10) DEFAULT 0 ,              _construction CHAR(10) DEFAULT 0 ,
                _pet CHAR(10) DEFAULT 0 ,                  _a CHAR(10)); ');
    sqlite_query($GBDBuser, 'CREATE INDEX "act_code" ON "action" ("_code")');
    sqlite_query($GBDBuser, 'CREATE TABLE
                BuildingParts (
                _id INT PRIMARY KEY,
                _name CHAR(200) DEFAULT 0 ,
                _itemName CHAR(50) DEFAULT 0 ,
                _itemCode CHAR(5) DEFAULT 0 ,
                _need CHAR(5) DEFAULT 0 ,
                _part CHAR(5)  DEFAULT 0,
                _UnitBuildCode CHAR(5)  DEFAULT 0,
                _UnitBuildName CHAR(50)  DEFAULT 0,
                _ObjHave CHAR(5)  DEFAULT 0,
                _ObjId CHAR(5)  DEFAULT 0,
                _ObjState CHAR(5)  DEFAULT 0,
                _action CHAR(5)  DEFAULT 0
                 ); ');
    sqlite_query($GBDBuser, 'CREATE INDEX "SC_itemcode" ON "BuildingParts" ("_itemcode")');
    sqlite_query($GBDBuser, 'CREATE INDEX "SC_part" ON "BuildingParts" ("_part")');
    // wait for DB to be created.
    sleep(2);
    AddLog2('GB DB user: create done');
    global $GBmainSet;
    global $this_plugin;
    return;
}
//*********************************
//  Open the Main database
//*********************************
function GBDBmain_init($origin) {
    AddLog2('GB DB main: init');
    global $GBmainSet;
    global $GBDBmain;
    global $GBDBuser;
    $GBDBmain = sqlite_open("plugins/GiftBox/" . GBox_DB_main) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    if (!$GBDBmain) {
        AddLog2('GB DB Error: main init' . $err);
        return "fail";
    }
    sqlite_query($GBDBmain, 'PRAGMA cache_size=200000');
    sqlite_query($GBDBmain, 'PRAGMA synchronous=OFF');
    sqlite_query($GBDBmain, 'PRAGMA count_changes=OFF');
    sqlite_query($GBDBmain, 'PRAGMA journal_mode=MEMORY');
    sqlite_query($GBDBmain, 'PRAGMA temp_store=MEMORY');
    // check if the tables exist or need to be created.
    $GBSQL = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'units'";
    $result = sqlite_query($GBDBmain, $GBSQL) or GB_AddLog("Tables need to be created. ");
    if (sqlite_num_rows($result) == 0) {
        GBDBmain_create();
    }
    return;
}
//*********************************
//  CREATE the Main database
//*********************************
function GBDBmain_create() {
    AddLog2('GB DB main: create');
    global $GBDBmain;
    global $GBDBuser;
    sqlite_query($GBDBmain, 'CREATE TABLE unitbuilding (
                id INT PRIMARY KEY,                _buildingcode CHAR(5),
                _itemcode CHAR(5),                 _part CHAR(5),
                _item CHAR(50),                    _name CHAR(50),
                _itemName CHAR(50),                _need CHAR(50),
                _limit CHAR(5),                    _level CHAR(50),
                _capacity CHAR(50),                _component_for CHAR(50),
                _storageType_itemName CHAR(50),
                _matsNeeded CHAR(50)
              )');
    sqlite_query($GBDBmain, 'CREATE TABLE
                StorageConfig (
                _id INT PRIMARY KEY,                _name CHAR(200) DEFAULT 0 ,
                _allowKeyword CHAR(50) DEFAULT 0 ,  _itemName CHAR(50) DEFAULT 0 ,
                _itemCode CHAR(5) DEFAULT 0 ,       _need CHAR(5) DEFAULT 0 ,
                _limit CHAR(5) DEFAULT 0 ,          _part CHAR(5)  DEFAULT 0 ); ');
    sqlite_query($GBDBmain, 'CREATE INDEX "SC_itemcode" ON "StorageConfig" ("_itemcode")');
    sqlite_query($GBDBmain, 'CREATE INDEX "SC_part" ON "StorageConfig" ("_part")');
    sqlite_query($GBDBmain, 'CREATE TABLE
                gamesettings (
                _id INT PRIMARY KEY,    _set CHAR(10) unique,
                _val CHAR(10)); ');
    sqlite_query($GBDBmain, 'CREATE TABLE
              units (
                id INT PRIMARY KEY,       _code CHAR(5),          _name CHAR(50),           _giftable CHAR(10),
                _type CHAR(50),           _subtype CHAR(50),      _buyable CHAR(10),        _placeable CHAR(10),
                _limit CHAR(5),           _className CHAR(50),    _requiredLevel CHAR(50),  _cost CHAR(50)  DEFAULT 0,
                _sizeX CHAR(3) DEFAULT 0, _sizeY CHAR(3) DEFAULT 0, _image_icon CHAR(150),    _cash CHAR(5),
                _limitedStart CHAR(50),   _limitedEnd CHAR(50),   _XP CHAR(5),              _coinYield CHAR(50),
                _finishedName  CHAR(50),  _action CHAR(50),       _actionText CHAR(50),     _insanityProbability CHAR(50),
                _baby CHAR(50),           _expansion CHAR(50),    _capacity CHAR(5),        _storageSize CHAR(5),
                _storageType_itemClass CHAR(50),  iconurl CHAR(100), _display CHAR(10),
                _keyword CHAR(50),                _matsNeeded CHAR(5)                   )');
    sqlite_query($GBDBmain, 'CREATE INDEX "units_code" ON "units" ("_code")');
    sqlite_query($GBDBmain, 'CREATE INDEX "units_name" ON "units" ("_name")');
    sqlite_query($GBDBmain, 'CREATE INDEX "units_StorItemCl" ON "units" ("_storageType_itemClass")');
    sqlite_query($GBDBmain, 'CREATE INDEX "Uncommon" ON "units" ("_type", "_limitedEnd")');
    sqlite_query($GBDBmain, 'CREATE INDEX "Display" ON "units" ("_type", "_name", "_display")');
    // wait for DB to be created.
    sleep(2);
    AddLog2('GB DB main: create done');
    list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
    if ($userId == "") {
        AddLog2("GB fail (userId unknown");
        return "fail";
    }
    AddLog2('GB DB main: Updating units.');
    GB_gameSettings_SQL($flashRevision);
    return;
}
//------------------------------------------------------------------------------
// Qs for quoting the string = SQL quoting
//------------------------------------------------------------------------------
function Qs($temp) {
    return "'" . $temp . "'";
}
//------------------------------------------------------------------------------
// Statistics | add stat to file
//------------------------------------------------------------------------------
function GB_Stat3($code, $name, $amount, $action) { // _code  _action  _name  _date _number
    global $GBDBuser;
    $today = @date("Y-M-d");
    $GBSQL = "SELECT * FROM stats WHERE _code = '$code' AND _date = '$today' AND _action = '$action'";
    $result = sqlite_query($GBDBuser, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GB_result = sqlite_fetch_all($result) or GB_AddLog("*** SQL Error *** " . $GBSQL);
        $NewNumber = $GB_result['0']['_number'] + $amount;
        $GBSQL = "UPDATE stats set _number=" . Qs($NewNumber) . " WHERE _code = '$code' AND _date = '$today' AND _action = '$action'";
        //GB_AddLog ("*** STATS *** GB_result " . $GB_result  );
        //GB_AddLog ("*** STATS *** items to add " . $NewNumber . " amount: " . $amount );

    } else {
        $GBSQL = "INSERT INTO stats(_code,_action,_name,_date,_number) VALUES(" . Qs($code) . "," . Qs($action) . "," . Qs($name) . "," . Qs($today) . "," . Qs($amount) . ")";
    }
    //GB_AddLog ("UpDate Set: " . $GBSQL );
    sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    return "OK";
}
//------------------------------------------------------------------------------
// Add to SQL if exists in XML
//------------------------------------------------------------------------------
function GB_xml_1($type, $type_name, $xml_name, $code, $GBDBmain, $Item) {
    if ($type == $type_name) {
        $temp = $Item->getElementsByTagName($xml_name);
        if ($temp->length != 0) {
            $capacity = Qs($temp->item(0)->nodeValue);
            $GBSQL = "UPDATE units SET _$xml_name = $capacity WHERE _code == $code ";
            sqlite_query($GBDBmain, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
        }
    }
}
//------------------------------------------------------------------------------
// GB_get_World_storge_xml SQL version
//------------------------------------------------------------------------------
function GB_gameSettings_SQL($flashRevision) {
    DebugLog(" >> GB_gamesettings_SQL");
    global $GB_Setting;
    global $GBDBmain;
    global $GBDBuser;
    // now we are here, file exist and was changed
    $filelocal = "gameSettings.xml";
    $filelocal = './farmville-xml/' . $flashRevision . '_items.xml';
    if (!file_exists($filelocal)) {
        AddLog2("GB: We can not find the new file: " . $filelocal);
    } else {
        AddLog2("GB: New items.xml found updating SQL database " . $flashRevision);
    }
    // empty the all storages
    $GBSQL = "DELETE FROM units";
    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    // empty the all storages
    $GBSQL = "DELETE FROM unitbuilding";
    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    sqlite_query($GBDBmain, 'BEGIN;');
    $xmlDoc = new DOMDocument();
    $xmlDoc->load($filelocal);
    $Items = $xmlDoc->getElementsByTagName("item");
    $i = 0;
    foreach($Items as $Item) {
        $i++;
        if ($Item->hasAttribute('code')) {
            $code = Qs($Item->getAttribute('code'));
        } else {
            $code = "'NULL'";
        }
        if ($Item->hasAttribute('name')) {
            $itemname = Qs($Item->getAttribute('name'));
        } else {
            $itemname = "NULL";
        }
        if ($Item->hasAttribute('giftable')) {
            $giftable = Qs($Item->getAttribute('giftable'));
        } else {
            $giftable = "NULL";
        }
        if ($Item->hasAttribute('type')) {
            $type = Qs($Item->getAttribute('type'));
        } else {
            $type = "NULL";
        }
        if ($Item->hasAttribute('subtype')) {
            $subtype = Qs($Item->getAttribute('subtype'));
        } else {
            $subtype = "NULL";
        }
        if ($Item->hasAttribute('buyable')) {
            $buyable = Qs($Item->getAttribute('buyable'));
        } else {
            $buyable = "NULL";
        }
        if ($Item->hasAttribute('placeable')) {
            $placeable = Qs($Item->getAttribute('placeable'));
        } else {
            $placeable = "NULL";
        }
        //     if($Item->hasAttribute('sortPriority')) { $sortPriority  = Qs($Item->getAttribute('sortPriority')); }else{$sortPriority = "NULL";}
        if ($Item->hasAttribute('className')) {
            $className = Qs($Item->getAttribute('className'));
        } else {
            $className = "NULL";
        }
        //     if($Item->hasAttribute('market')) { $market  = Qs($Item->getAttribute('market')); }else{$market = "NULL";}
        //     if($Item->hasAttribute('statsType')) { $statsType  = Qs($Item->getAttribute('statsType')); }else{$statsType = "NULL";}
        //     if($Item->hasAttribute('present')) { $present  = Qs($Item->getAttribute('present')); }else{$present = "NULL";}
        //     if($Item->hasAttribute('')) { $  = Qs($Item->getAttribute('')); }else{$ = "NULL";}
        $GBSQL = "INSERT INTO units(_code,_name,_giftable,_type,_subtype, _buyable, _placeable, _className )";
        $GBSQL.= " values($code,$itemname,$giftable,$type,$subtype, $buyable, $placeable, $className)";
        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
        GB_xml_1($type, $type, "cash", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "cost", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "sizeX", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "sizeY", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "limitedStart", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "limitedEnd", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "requiredLevel", $code, $GBDBmain, $Item);
        //     GB_xml_1($type, $type, "imageScale", $code, $GBDBmain, $Item );
        //     GB_xml_1($type, $type, "growTime", $code, $GBDBmain, $Item );
        //     GB_xml_1($type, $type, "coinYield", $code, $GBDBmain, $Item );
        GB_xml_1($type, $type, "actionText", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "storageSize", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "limit", $code, $GBDBmain, $Item);
        GB_xml_1($type, $type, "keyword", $code, $GBDBmain, $Item); // added 2010-06-29
        //     GB_xml_1($type, $type, "", $code, $GBDBmain, $Item );
        GB_xml_1($type, "'building'", "capacity", $code, $GBDBmain, $Item);
        GB_xml_1($type, "'building'", "storageSize", $code, $GBDBmain, $Item);
        GB_xml_1($type, "'building'", "expansion", $code, $GBDBmain, $Item);
        GB_xml_1($type, "'building'", "matsNeeded", $code, $GBDBmain, $Item);
        GB_xml_1($type, "'building'", "finishedName", $code, $GBDBmain, $Item);
        //     GB_xml_1($type, "'building'", "", $code, $GBDBmain, $Item );
        $images = $Item->getElementsByTagName("image");
        foreach($images as $image) {
            if ($image->hasAttribute('name')) {
                $name = $image->getAttribute('name');
            } else {
                $name = "No";
            }
            if ($name == "icon") {
                if ($image->hasAttribute('url')) {
                    $url = Qs($image->getAttribute('url'));
                } else {
                    $url = "''";
                }
                $GBSQL = "UPDATE units SET iconurl = $url WHERE _code == $code ";
                sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
            }
        } // end foreach  $image
        if ($className == "'BuildingPart'") {
            $components = $Item->getElementsByTagName("component");
            foreach($components as $component) {
                $buildingTypes = $component->getElementsByTagName("buildingType");
                if ($buildingTypes->length != 0) {
                    foreach($buildingTypes as $buildingType) {
                        $buildingTypename = Qs($buildingType->textContent);
                        $GBSQL = "INSERT INTO unitbuilding( _component_for, _name, _itemcode)";
                        $GBSQL.= " values($buildingTypename, $itemname, $code)";
                        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                    }
                } //

            } //

        }
        if ($type == "'building'") {
            $storageTypes = $Item->getElementsByTagName("storageType");
            foreach($storageTypes as $storageType) {
                $itemNames = $storageType->getElementsByTagName("itemName");
                if ($itemNames->length != 0) {
                    foreach($itemNames as $itemName) {
                        if ($itemName->hasAttribute('need')) {
                            $need = Qs($itemName->getAttribute('need'));
                        } else {
                            $need = "'NULL'";
                        }
                        if ($itemName->hasAttribute('limit')) {
                            $limit = Qs($itemName->getAttribute('limit'));
                        } else {
                            $limit = "'NULL'";
                        }
                        if ($itemName->hasAttribute('part')) {
                            $part = Qs($itemName->getAttribute('part'));
                        } else {
                            $part = "'NULL'";
                        }
                        $Itemcodename = Qs($itemName->textContent);
                        $GBSQL = "INSERT INTO unitbuilding( _buildingcode, _itemcode, _name, _need, _limit, _part)";
                        $GBSQL.= " values($code, 'NULL', $Itemcodename, $need, $limit, $part)";
                        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                    }
                } // itemcount < 1, so skip this building

            } // end foreach  $storageTypes
            // added 2010-06-29 to detect itemClass
            if ($type == "'building'") {
                $storageTypes = $Item->getElementsByTagName("storageType");
                foreach($storageTypes as $storageType) {
                    if ($storageType->hasAttribute('itemClass')) {
                        $itemClass = Qs($storageType->getAttribute('itemClass'));
                        $GBSQL = "UPDATE units SET _storageType_itemClass = $itemClass WHERE _code == $code ";
                        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                    }
                }
            } // end foreach  $storageTypes
            $upgrades = $Item->getElementsByTagName("upgrade");
            foreach($upgrades as $upgrade) {
                $upglevel = Qs($upgrade->getAttribute('level'));
                $upgcapacity = Qs($upgrade->getAttribute('capacity'));
                // let's store the levels and capacity for this building
                $GBSQL = "INSERT INTO unitbuilding( _buildingcode, _level, _capacity)";
                $GBSQL.= " values($code, $upglevel, $upgcapacity)";
                sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                $upgparts = $storageType->getElementsByTagName("part");
                if ($upgparts->length != 0) {
                    foreach($upgparts as $upgpart) {
                        if ($itemName->hasAttribute('name')) {
                            $upgpartname = Qs($itemName->getAttribute('name'));
                        } else {
                            $upgpartname = "'NULL'";
                        }
                        if ($itemName->hasAttribute('need')) {
                            $upgpartneed = Qs($itemName->getAttribute('need'));
                        } else {
                            $upgpartneed = "'NULL'";
                        }
                        $GBSQL = "INSERT INTO unitbuilding( _buildingcode, _itemcode, _name, _need, _limit, _part, _level, _capacity)";
                        $GBSQL.= " values($code, 'NULL', $upgpartname, $upgpartneed, '', 'true', $upglevel, $upgcapacity)";
                        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                    }
                }
            } // end foreach  upgrade

        } // end building

    } // for each item
    sqlite_query($GBDBmain, 'COMMIT;');
    sqlite_query($GBDBmain, 'BEGIN;');
    // Update itmes with end date
    $GBSQL = "UPDATE units SET _display = 'Uncommon' WHERE _type = 'decoration' AND _limitedEnd IS NOT NULL";
    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    // Update the Deco_Small
    $Items = array('flag', 'gnome', 'haybale', 'barrel', 'scarecrow', 'flower', 'bird', 'crate', 'grass');
    $TabName = 'Deco_Small';
    foreach($Items as $Item) {
        $GBSQL = "UPDATE units SET _display = '" . $TabName . "' WHERE _name LIKE '%" . $Item . "%' AND _type = 'decoration' ";
        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    }
    // Update the Deco_2
    $Items = array('mystery', 'bench', 'bike', 'fence', 'topiary', 'grab', 'hay', 'hedge', 'water', 'sign', 'bush', 'post', 'smal', 'teddy', '_deco');
    $TabName = 'Deco_2';
    foreach($Items as $Item) {
        $GBSQL = "UPDATE units SET _display = '" . $TabName . "' WHERE _name LIKE '%" . $Item . "%' AND _type = 'decoration' ";
        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    }
    // Update the Uncommon  items
    $Items = array('master', 'cropcircl', 'ring', 'snow', 'nutcracker', 'ornament', 'firework', 'light', 'cotton', 'eifel', 'football', 'ice', 'spooky', 'soldier', 'nachos', 'mask');
    $TabName = 'Uncommon';
    foreach($Items as $Item) {
        $GBSQL = "UPDATE units SET _display = '" . $TabName . "' WHERE _name LIKE '%" . $Item . "%' AND _type = 'decoration' ";
        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    }
    // Update the Special items
    $Items = array('tw_', 'easter_item', 'valentine_', 'potofgold_', 'present_');
    $TabName = 'Specials';
    foreach($Items as $Item) {
        $GBSQL = "UPDATE units SET _display = '" . $TabName . "' WHERE _name LIKE '%" . $Item . "%' AND _type = 'decoration' ";
        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    }
    // Update the collection items
    $GBSQL = "UPDATE units SET _display = 'Collections' WHERE iconurl LIKE '%collect%' AND _type = 'decoration' ";
    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    // Update itmes that did not got assigned
    $GBSQL = "UPDATE units SET _display = 'Deco_rest' WHERE _type = 'decoration' AND _display IS NULL";
    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    // Done
    sqlite_query($GBDBmain, 'COMMIT;');
    // update flash revision in database.
    GB_SQL_updSetting($GBDBmain, 'gamesettings', 'flashversion', $flashRevision);
    AddLog2("GB xml SQL update - saved " . $i);
    sqlite_query($GBDBmain, 'COMMIT;');
    AddLog2("GB xml SQL update - comited " . $i);
    // added 2010-09-09 new                                                       *****
    $filelocal = './farmville-xml/' . $flashRevision . '_StorageConfig.xml';
    if (!file_exists($filelocal)) {
        AddLog2("GB: We can not find the new file: " . $filelocal);
        return "Failed";
    } else {
        AddLog2("GB: New StorageConfig.xml found updating SQL database " . $flashRevision);
    }
    // now we are here, file exist and was changed
    AddLog2("GB New StorageConfig.xml found updating SQL database");
    // empty the all StorageConfig
    $GBSQL = "DELETE FROM StorageConfig";
    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    // lock the database for speed
    sqlite_query($GBDBmain, 'BEGIN;');
    $xmlDoc = new DOMDocument();
    $xmlDoc->load($filelocal); // update 2010-9-9
    $StorageBuildings = $xmlDoc->getElementsByTagName("StorageBuilding");
    $i = 0;
    foreach($StorageBuildings as $StorageBuilding) {
        $i++;
        if ($StorageBuilding->hasAttribute('name')) {
            $name = Qs($StorageBuilding->getAttribute('name'));
        } else {
            $name = "'NULL'";
        }
        // check if there are allowKeyword
        $allowKeywords = $StorageBuilding->getElementsByTagName("allowKeyword");
        if ($allowKeywords->length != 0) {
            $allowKeyword = Qs($allowKeywords->item(0)->nodeValue);
        } else {
            $allowKeyword = "'-'";
        }
        // check if there are itemNames
        $itemNames = $StorageBuilding->getElementsByTagName("itemName");
        if ($itemNames->length != 0) {
            foreach($itemNames as $itemName) {
                if ($itemName->hasAttribute('need')) {
                    $need = Qs($itemName->getAttribute('need'));
                } else {
                    $need = "'0'";
                }
                if ($itemName->hasAttribute('limit')) {
                    $limit = Qs($itemName->getAttribute('limit'));
                } else {
                    $limit = "'0'";
                }
                if ($itemName->hasAttribute('part')) {
                    $part = Qs($itemName->getAttribute('part'));
                } else {
                    $part = "'0'";
                }
                $Itemcodename = Qs($itemName->textContent);
                $GBSQL = "INSERT INTO StorageConfig( _name, _allowKeyword, _itemName, _need, _limit, _part)";
                $GBSQL.= " values($name, $allowKeyword, $Itemcodename, $need, $limit, $part)";
                sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
            }
        } else { // check allowedClass
            $vAllowedClasses = $StorageBuilding->getElementsByTagName("allowedClass");
            if ($vAllowedClasses->length != 0) {
                foreach($vAllowedClasses as $vAllowedClass) {
                    $vClass = $vAllowedClass->getAttribute('type');
                    $vUnits = Units_GetByType(strtolower($vClass));

                    foreach($vUnits as $vUnit) {
                        $Itemcodename = Qs($vUnit['code']);
                        $Itemname = Qs($vUnit['name']);
                        $need = "'0'";
                        $limit = "'0'";
                        $part = "'0'";
                        $allowKeyword = "'-'";
                        $GBSQL = "INSERT INTO StorageConfig( _name, _allowKeyword, _itemName, _itemCode, _need, _limit, _part)";
                        $GBSQL.= " values($name, $allowKeyword, $Itemname, $Itemcodename, $need, $limit, $part)";
                        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                    }
                }
            } else { // no itemnames but still need to write the building
                $GBSQL = "INSERT INTO StorageConfig( _name, _allowKeyword)";
                $GBSQL.= " values($name, $allowKeyword)";
                sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
            }
        }
        if($allowKeyword<>"'-'") {
            $GBSQL = "INSERT INTO StorageConfig( _name, _allowKeyword)";
            $GBSQL.= " values($name, $allowKeyword)";
            sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
        }

        $vSQL="select name from units where content=".$name." and field='storageType'";
        $vResult0=Units_GetSQL($vSQL);
        if(strlen($vResult0[0]['name'])>0) {
#        if($name=="'pigpen'") { #pigpen_finished
#            $vPigpenBuilding = GetObjects("PigpenBuilding");
            $vSQL='select max(content) as level from units where name="'.$vResult0[0]['name'].'" and field="upgrade_level"';
            $vResult=Units_GetSQL($vSQL);
            if(strlen($vResult[0]['level'])>0) {
                $vSQL='select content as part from units where name="'.$vResult0[0]['name'].'" and field="upgrade_'.$vResult[0]['level'].'_part"';
                $vResult2=Units_GetSQL($vSQL);
                foreach($vResult2 as $vRow) {
                    $vSQL='select content as amount from units where name="'.$vResult0[0]['name'].'" and field="upgrade_'.$vResult[0]['level'].'_'.$vRow['part'].'_need"';
                    $vResult3=Units_GetSQL($vSQL);
                    $vAmount=$vResult3[0]['amount'];
                    $allowKeyword = "'-'";
                    $Itemname = Qs($vRow['part']);
                    $Itemcodename = Qs(Units_GetCodeByName($vRow['part']));
                    $need = Qs($vAmount);
                    $limit = Qs(0);
                    $part = Qs('true');
#                    $vSQL="select * from StorageConfig where _name=$name and _itemName=$Itemname";
#                    $result = sqlite_query($GBDBmain, $vSQL) or GBSQLError($GBDBmain, $vSQL);
#                    if (sqlite_num_rows($result) == 0) {
#                        $GBSQL = "INSERT INTO StorageConfig( _name, _allowKeyword, _itemName, _itemCode, _need, _limit, _part)";
#                        $GBSQL.= " values($name, $allowKeyword, $Itemname, $Itemcodename, $need, $limit, $part)";
#                        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
#                    }
                    $vSQL="delete from StorageConfig where _name=$name and _itemName=$Itemname";
                    sqlite_query($GBDBmain, $vSQL) or GBSQLError($GBDBmain, $vSQL);
                    $GBSQL = "INSERT INTO StorageConfig( _name, _allowKeyword, _itemName, _itemCode, _need, _limit, _part)";
                    $GBSQL.= " values($name, $allowKeyword, $Itemname, $Itemcodename, $need, $limit, $part)";
                    sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                }
            }
        }

    } // end foreach StorageBuilding
    $FeatureCreditStorages = $xmlDoc->getElementsByTagName("FeatureCreditStorage");
    foreach($FeatureCreditStorages as $FeatureCreditStorage) {
        $i++;
        if ($FeatureCreditStorage->hasAttribute('name')) {
            $name = Qs($FeatureCreditStorage->getAttribute('name'));
        } else {
            $name = "'NULL'";
        }
        // check if there are itemNames
        $itemNames = $FeatureCreditStorage->getElementsByTagName("itemName");
        if ($itemNames->length != 0) {
            foreach($itemNames as $itemName) {
                $Itemcodename = Qs($itemName->textContent);
                $GBSQL = "INSERT INTO StorageConfig( _name, _itemName)";
                $GBSQL.= " values($name, $Itemcodename)";
                sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
            }
        } // end itemname

    } //end foreach FeatureCreditStorages
    // update all codes in the unitbuilding db
    $query = sqlite_query($GBDBmain, "SELECT _itemName FROM StorageConfig WHERE _itemcode == '0'");
    $result = sqlite_fetch_all($query, SQLITE_ASSOC);
    foreach($result as $entry) {
        $GBSQL = "SELECT _code from units where _name == " . Qs($entry['_itemName']) . " limit 1";
        $result2 = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
        if (sqlite_num_rows($result2) > 0) {
            $itemcodetemp = Qs(sqlite_fetch_single($result2));
        } else {
            $itemcodetemp = "'-'";
        }
        //AddLog2("Giftbox: looking for $Itemcodename found $itemcodetemp");
        $GBSQL = "UPDATE StorageConfig SET _itemCode=$itemcodetemp WHERE _itemName = " . Qs($entry['_itemName']);
        //AddLog2("GB SQL = " .$GBSQL);
        sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    }
    // write all changes into database
    sqlite_query($GBDBmain, 'COMMIT;');
    AddLog2("GB StorageBuilding SQL update - comited " . $i);
    DebugLog(" << GB_gamesettings_SQL");
    return;
}
//------------------------------------------------------------------------------
//  Update user settings
//------------------------------------------------------------------------------
function GB_Update_User_Setting($setting, $valeu) {
    global $GBDBuser;
    $GBSQL = "SELECT _val FROM gamesettings WHERE _set = '$setting' ";
    $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GBSQL = "UPDATE gamesettings set _val=" . Qs($valeu) . " WHERE _set=" . Qs($setting) . " ";
    } else {
        $GBSQL = "INSERT OR REPLACE INTO gamesettings(_set,_val) VALUES(" . Qs($setting) . "," . Qs($valeu) . ")";
    }
    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    return "OK";
}
//------------------------------------------------------------------------------
//  Get user settings
//------------------------------------------------------------------------------
function GB_Get_User_Setting($setting) {
    global $GBDBuser;
    $GBSQL = "SELECT _val FROM gamesettings WHERE _set = '$setting' ";
    $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GB_result = sqlite_fetch_all($result) or GB_AddLog("*** SQL Error *** " . $GBSQL);
        return $GB_result['0']['_val'];
    } else {
        return "Not Found";
    }
    return "Fail";
}
//------------------------------------------------------------------------------
//
//------------------------------------------------------------------------------
function GBuserQ($GBSQL) {
    global $GBDBuser;
    $result1 = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    if (sqlite_num_rows($result1) > 0) {
        $GB_result = sqlite_fetch_all($result1);
    } else {
        $GB_result = array();
    }
    return $GB_result;
}
//------------------------------------------------------------------------------
// GBSQLGetObjByID  = Get Object details by object ID
//------------------------------------------------------------------------------
function GBSQLGetObjByID($ObjID) {
    global $GBDBuser;
    $GB_result = array();
    $GBSQL = "SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'id' AND _val = '" . $ObjID . "')";
    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    while ($entry = sqlite_fetch_array($query, SQLITE_ASSOC)) {
        $GB_result[$entry['_set']] = $entry['_val'];
        if ($entry['_set'] == 'contents') {
            $GB_result[$entry['_set']] = unserialize($entry['_val']);
        }
        if ($entry['_set'] == 'expansionParts') {
            $GB_result[$entry['_set']] = unserialize($entry['_val']);
        }
        if ($entry['_set'] == 'position') {
            $GB_result[$entry['_set']] = unserialize($entry['_val']);
        }
    }
    return $GB_result;
}
//------------------------------------------------------------------------------
// GBSQLGetUnitByName  = Get Object details by object ID
//------------------------------------------------------------------------------
function GBSQLgetAction($code) {
    global $GBDBuser;
    $GBSQL = "SELECT * FROM action WHERE _code = '" . $code . "' LIMIT 1";
    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $entry = sqlite_fetch_all($query, SQLITE_ASSOC);
    if (array_key_exists("0", $entry)) {
        return $entry['0'];
    } else {
        return array();
    }
}
//------------------------------------------------------------------------------
// GBSQLGetUnitByName  = Get Object details by object ID
//------------------------------------------------------------------------------
function GBSQLGetUnitByName($name) {
    global $GBDBmain;
    $GB_result = array();
    $GBSQL = "SELECT * FROM units WHERE _name = '" . $name . "' LIMIT 1";
    $query = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $entry = sqlite_fetch_array($query, SQLITE_ASSOC);
    return $entry;
}
//------------------------------------------------------------------------------
// GBSQLGetUnitByCode  = Get Object details by object ID
//------------------------------------------------------------------------------
function GBSQLGetUnitByCode($code) {
    global $GBDBmain;
    $GB_result = array();
    $GBSQL = "SELECT * FROM units WHERE _code = '" . $code . "' LIMIT 1";
    $query = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $entry = sqlite_fetch_array($query, SQLITE_ASSOC);
    return $entry;
}
function GB_SQL_updLang($DB, $table, $set, $val) {
    // resource availible?
    if (!$DB) {
        AddLog2('GB SQL Error: Database not open');
        return "'NULL'";
    }
    // check if table exist
    $GBSQL = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = '$table'";
    $result = sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    if (sqlite_num_rows($result) == 0) {
        AddLog2('GB SQL Error: Table does not exist');
        return "'NULL'";
    }
    if ($set == '') {
        AddLog2('GB SQL Error: Setting empty');
        return "'NULL'";
    }
    // all is good to go. lets insert this data
    // is setting already in DB? than we need to update the valeu.
    $GBSQL = "SELECT _nice FROM $table WHERE _raw = '$set' ";
    $result = sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GBSQL = "UPDATE $table set _raw=" . Qs($val) . " WHERE _nice=" . Qs($set) . " ";
    } else {
        $GBSQL = "INSERT INTO $table(_raw,_nice) VALUES(" . Qs($set) . "," . Qs($val) . ")";
    }
    sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    return "OK";
}
// $field = _selling
function GB_SQL_updAction($field, $code, $val) {
    global $GBDBuser;
    $GBSQL = "SELECT _code FROM action WHERE _code = '$code' ";
error_log($GBSQL);
    $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GBSQL = "UPDATE action set $field=" . Qs($val) . " WHERE _code=" . Qs($code) . " ";
    } else {
        $GBSQL = "INSERT INTO action(_code," . $field . ") VALUES(" . Qs($code) . "," . Qs($val) . ")";
    }
error_log($GBSQL);
    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    return "OK";
}
function GB_SQL_updSetting($DB, $table, $set, $val) {
    // resource availible?
    if (!$DB) {
        AddLog2('GB SQL Error: Database not open');
        return "'NULL'";
    }
    // check if table exist
    $GBSQL = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = '$table'";
    $result = sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    if (sqlite_num_rows($result) == 0) {
        AddLog2('GB SQL Error: Table does not exist');
        return "'NULL'";
    }
    if ($set == '') {
        AddLog2('GB SQL Error: Setting empty');
        return "'NULL'";
    }
    // all is good to go. lets insert this data
    // is setting already in DB? than we need to update the valeu.
    $GBSQL = "SELECT _val FROM $table WHERE _set = '$set' ";
    $result = sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GBSQL = "UPDATE $table set _val=" . Qs($val) . " WHERE _set=" . Qs($set) . " ";
    } else {
        $GBSQL = "INSERT INTO $table(_set,_val) VALUES(" . Qs($set) . "," . Qs($val) . ")";
    }
    //   AddLog2("xml SQL ".$GBSQL );
    sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    return "OK";
}
function GB_SQL_updActionCode($code, $val) {
    //                _code    _place_on_farm    _place_in_build
    //                _place_in_amount           _place_in_max
    //                _place_in_special         _target
    //                _selling    _keep         _construction     _a
    //          insert or replace INTO action (_code, _target) VALUES ('aa', "sss")
    sqlite_query($GBDBuser, 'BEGIN;');
    $GBSQL = "INSERT OR REPLACE INTO action(_) VALUES ()";
    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    sqlite_query($GBDBuser, 'COMMIT;');
    global $GBDBuser;
    // resource availible?
    if (!$GBDBuser) {
        AddLog2('GB SQL Error: Database not open');
        return "'NULL'";
    }
    if ($code == '') {
        AddLog2('GB SQL Error: code empty');
        return "'NULL'";
    }
    // all is good to go. lets insert this data
    // is setting already in DB? than we need to update the valeu.
    $GBSQL = "SELECT _val FROM action WHERE _code = '$code' ";
    $result = sqlite_query($DB, $GBSQL) or GBSQLError($DB, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GBSQL = "UPDATE action set _val=" . Qs($val) . " WHERE _code=" . Qs($code) . " ";
    } else {
        $GBSQL = "INSERT INTO action(_code,_val) VALUES(" . Qs($code) . "," . Qs($val) . ")";
    }
    //   AddLog2("xml SQL ".$GBSQL );
    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    return "OK";
}
function GB_get_friendlyName($raw) {
    return Units_GetRealnameByName($raw);
}

//------------------------------------------------------------------------------
// new building parts database
//------------------------------------------------------------------------------
function GB_BuildingParts4() {
    global $GBDBmain;
    global $GBDBuser;
    global $GB_Setting;
    if (array_key_exists('ExclConstr', $GB_Setting)) {
        $ExclConstr = unserialize($GB_Setting['ExclConstr']);
    } else {
        $ExclConstr = array();
    }
    //empty the database table.
    $GBSQL = "DELETE FROM BuildingParts";
    #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    // first check if all the building parts are in the table
#    $GBSQL = "SELECT DISTINCT _name, _itemName, _itemCode, _need, _par FROM StorageConfig WHERE _part = 'true'";
    $GBSQL = "SELECT _name, _itemName, _itemCode, _part, 0 as _need FROM StorageConfig WHERE _part = 'true' group by _name, _itemName, _itemCode, _part order by  _name, _itemName";
    $query = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $buildingParts = sqlite_fetch_all($query);
    foreach($buildingParts as $buildingPart) {
        //               _name, _itemName, _itemCode, _need, _part
        $GBSQL = "INSERT OR REPLACE INTO BuildingParts(_name, _itemName, _itemCode, _need, _part) ";
        $GBSQL.= " VALUES (" . Qs($buildingPart['_name']) . "," . Qs($buildingPart['_itemName']) . "," . Qs($buildingPart['_itemCode']) . "," . Qs($buildingPart['_need']) . "," . Qs($buildingPart['_part']) . ");";
        #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
        $result = sqlite_query($GBDBuser, $GBSQL);
    }
    //  fill the Unit part.             _UnitBuildCode, _UnitBuildName,
    // first look for all building that could contain building part
    $GBSQL = "SELECT DISTINCT _name FROM BuildingParts";
    #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    $UnitNames = sqlite_fetch_all($query);
    foreach($UnitNames as $UnitName) {
        //$output .= "buildingName info:<br>";
        $GBSQL = "SELECT DISTINCT  _code,_name,_storageType_itemClass FROM units WHERE _storageType_itemClass = '" . $UnitName['_name'] . "'";
        #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
        $query = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
        $UnitInfo = sqlite_fetch_all($query);
        //got the info, now write into datebase
        $GBSQL = "UPDATE BuildingParts SET _UnitBuildCode=" . Qs($UnitInfo['0']['_code']) . ",_UnitBuildName=" . Qs($UnitInfo['0']['_name']) . " WHERE _name = " . Qs($UnitInfo['0']['_storageType_itemClass']);
        #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    }
    // now check the objects on the farm. For now only 1 of each can be in construction.
    $GBSQL = "SELECT DISTINCT _UnitBuildName FROM BuildingParts";
#    $GBSQL = "SELECT DISTINCT _UnitBuildName FROM BuildingParts WHERE _UnitBuildName = 'mysteryseedling'";
    #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    $UnitBuildNames = sqlite_fetch_all($query);
    foreach($UnitBuildNames as $UnitBuildName) {
        $ObjDatas = array();
        $GBSQL = "SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = '" . $UnitBuildName['_UnitBuildName'] . "')";
        #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
        // check if there was a building
        if (sqlite_num_rows($query) > 0) {
            $contineu = "ok";
        } else {
            continue;
        } //skip if there was no object.
        // now put this into array
        while ($entry = sqlite_fetch_array($query, SQLITE_ASSOC)) {
            $ObjDatas[$entry['_set']] = $entry['_val'];
            if ($entry['_set'] == 'contents') {
                $ObjDatas[$entry['_set']] = unserialize($entry['_val']);
            }
            if ($entry['_set'] == 'expansionParts') {
                $ObjDatas[$entry['_set']] = unserialize($entry['_val']);
            }
            //if($entry['_set'] == 'position')       {$GB_result[$entry['_set']] = unserialize($entry['_val'])  ;}

        }
        //check the content of the object.
        if (is_array($ObjDatas['contents'])) { //  the contents
            foreach($ObjDatas['contents'] as $Content) { //_ObjHave, _ObjId, _ObjState
                $GBSQL = "UPDATE BuildingParts SET _ObjHave=" . Qs($Content['numItem']) . " WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']) . " AND _itemCode = " . Qs($Content['itemCode']);
                #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
                $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            } // end contents
            $GBSQL = "UPDATE BuildingParts SET _ObjId=" . Qs($ObjDatas['id']) . ",_ObjState=" . Qs($ObjDatas['state']) . " WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']);
            #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
            $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        if (is_array($ObjDatas['expansionParts'])) { //  the contents
            foreach($ObjDatas['expansionParts'] as $Content=>$Amount) { //_ObjHave, _ObjId, _ObjState
                $GBSQL = "UPDATE BuildingParts SET _ObjHave=" . Qs($Amount) . " WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']) . " AND _itemCode = " . Qs($Content);
                #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
                $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            } // end contents
            $GBSQL = "UPDATE BuildingParts SET _ObjId=" . Qs($ObjDatas['id']) . ",_ObjState=" . Qs($ObjDatas['state']) . " WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']);
            #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
            $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        $vSQL="select content as name from units where name='".$UnitBuildName['_UnitBuildName']."' and field='storageType'";
        $vResult0=Units_GetSQL($vSQL);
        if(strlen($vResult0[0]['name'])>0) {
            $vSQL='select content from storage where name="'.$vResult0[0]['name'].'" and field="part"';
            $vResult=Units_GetSQL($vSQL);
            foreach($vResult as $vRow) {
                $vSQL='select content from storage where name="'.$vResult0[0]['name'].'" and field="'.$vRow['content'].'_need"';
                $vResult3=Units_GetSQL($vSQL);
                $vNeed=$vResult3[0]['content'];
                $GBSQL = "UPDATE BuildingParts SET _need=" . Qs($vNeed) . " WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']) . " AND _itemCode = " . Qs(Units_GetCodeByName($vRow['content']));
                #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
                $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            }
        }
        if (array_key_exists('expansionLevel', $ObjDatas)) {
            $vSQL='select content as part from units where name="'.$UnitBuildName['_UnitBuildName'].'" and field="upgrade_'.($ObjDatas['expansionLevel']+1).'_part"';
            $vResult=Units_GetSQL($vSQL);
            foreach($vResult as $vRow) {
                $vSQL='select content as amount from units where name="'.$UnitBuildName['_UnitBuildName'].'" and field="upgrade_'.($ObjDatas['expansionLevel']+1).'_'.$vRow['part'].'_need"';
                $vResult3=Units_GetSQL($vSQL);
                $vNeed=$vResult3[0]['amount'];
                $GBSQL = "UPDATE BuildingParts SET _need=" . Qs($vNeed) . " WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']) . " AND _itemCode = " . Qs(Units_GetCodeByName($vRow['part']));
                #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
                $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            }
        }

        // check if horsestablewhite is level 5, than do not add more parts.
        $DoConstruct = 'Y';
        if (array_key_exists('expansionLevel', $ObjDatas)) {
#            if ($ObjDatas['expansionLevel'] >= 5) $DoConstruct = 'N';
#            if ($UnitBuildName['_UnitBuildName'] == 'nurserybarn_finished' && $ObjDatas['expansionLevel'] >= 3) $DoConstruct = 'N';
#            if ($UnitBuildName['_UnitBuildName'] == 'hauntedhouse2010_finished' && $ObjDatas['expansionLevel'] >= 3) $DoConstruct = 'N';
#            if ($UnitBuildName['_UnitBuildName'] == 'horsestablewhite' && $ObjDatas['expansionLevel'] >= 5) $DoConstruct = 'N';
            $vSQL='select max(content) as level from units where name="'.$UnitBuildName['_UnitBuildName'].'" and field="upgrade_level"';
            $vResult=Units_GetSQL($vSQL);
            if(strlen($vResult[0]['level'])>0) {
                if ($ObjDatas['expansionLevel'] >= $vResult[0]['level']) $DoConstruct = 'N';
            }
        }

        // now check if there is any building part in the database,
        // if so, that means it is a construction building :-)
        #$GBSQL = "select sum(_ObjHave) AS total FROM BuildingParts WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']);
        #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
        #$query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        #$TotalBuildingParts = sqlite_fetch_all($query);
        #GB_AddLog ("*** Total Building parts: " . $TotalBuildingParts['0']['total'] );
        #if ($TotalBuildingParts['0']['total'] > 0 && $DoConstruct == 'Y') {
        if ($DoConstruct == 'Y') {
            $GBSQL = "UPDATE BuildingParts SET _action='construction' WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']);
            #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
            $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        // now check if the building is on the Eclude list
        if (array_key_exists($ObjDatas['itemName'], $ExclConstr)) {
            if ($ExclConstr[$ObjDatas['itemName']] == 'Exclude') {
                $GBSQL = "UPDATE BuildingParts SET _action='Exclude' WHERE _UnitBuildName = " . Qs($ObjDatas['itemName']);
                #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
                $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            }
        }
    }
    //               _ObjHave, _ObjId, _ObjState, _action

}
function GB_DetectBuildingParts4() {
    global $GBDBmain;
    global $GBDBuser;
    // Check that all building part are known in the action list.
    $GBSQL = "SELECT _itemCode FROM StorageConfig WHERE _part = 'true'";
    $query = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $buildingParts = sqlite_fetch_all($query);
    foreach($buildingParts as $buildingPart) {
        $GBSQL = "SELECT * FROM action WHERE _code = " . Qs($buildingPart['_itemCode']);
        $result = sqlite_query($GBDBuser, $GBSQL);
        if (sqlite_num_rows($result) > 0) {
            //$GB_result = sqlite_fetch_all($result) or GB_AddLog ("*** SQL Error *** " . $GBSQL );
            #$GBSQL = "UPDATE action set _construction='Y', _target = '0' WHERE _code = " . Qs($buildingPart['_itemCode']);
            $GBSQL = "UPDATE action set _construction='Y' WHERE _code = " . Qs($buildingPart['_itemCode']);
        } else {
            $GBSQL = "INSERT INTO action(_code, _target, _construction) ";
            $GBSQL.= " VALUES (" . Qs($buildingPart['_itemCode']) . ",'0','Y' );";
        }
        //GB_AddLog ("UpDate Set: " . $GBSQL );
        sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    } // all building part now in database.

}

function GB_DetectSpecials2() {
    global $GBDBmain;
    global $GBDBuser;
    #$SQL = '';
    $output = '<table class="sofT" cellspacing="0"><tr><td class="helpHed">Name Special</td>
                     <td class="helpHed">On farm?</td>
                     <td class="helpHed">Object ID</td>
                     <td class="helpHed">This can go into this Special</td></tr>';
    //SELECT * FROM units WHERE _capacity = '100'
    // select the special buildings

#    $GBSQL = "SELECT * FROM units WHERE (_capacity = '100' AND _name != 'holidaytree' AND _name != 'chickencoop5' AND _name != 'xukchickencoop5' AND _name != 'flowershed' AND _name != 'flowershedcache') or _name in ('thanksgivingbasket') ";
    $GBSQL = "SELECT * FROM units WHERE _name in ('".str_replace(',','\',\'',file_get_contents('plugins/GiftBox/specials.txt'))."') ";
    $result = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $GB_specials = sqlite_fetch_all($result);
    foreach($GB_specials as $GB_special) {
        // special = name of the special building
        $GBSQL = "SELECT _obj from objects WHERE _set = 'itemName' AND _val = '" . $GB_special['_name'] . "'";
        $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        $Objectbuildings = sqlite_fetch_all($result);
        //print_r($Objectbuildings);
        if (empty($Objectbuildings)) { // building does not exist.
            $output.= '<tr><td>' . Units_GetRealnameByName($GB_special['_name']) . ' (' . $GB_special['_name'] . ')</td><td>Is not on the farm</td>';
            $output.= '<td> - </td><td> - </td></tr>';
        } else { // we have the special building on the farm
            $GBSQL = "SELECT _val from objects WHERE _obj = '" . $Objectbuildings['0']['_obj'] . "' AND _set = 'id'";
            $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            $ObjectID = sqlite_fetch_all($result);
            $content = '';
            $GBSQL = "SELECT _itemCode FROM StorageConfig WHERE _name = '" . $GB_special['_storageType_itemClass'] . "'";
            $result1 = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
            if (sqlite_num_rows($result1) > 0) {
                $Items = sqlite_fetch_all($result1);
            } else {
                $Items = array();
            }
            foreach($Items as $Item) {
                $content.= Units_GetRealnameByCode($Item['_itemCode'])." [" . $Item['_itemCode'] . "]<br>";
                #GB_SQL_updAction("_place_in_special", $Item['_itemCode'], "999"); //$field, $code, $val
                #GB_SQL_updAction("_target", $Item['_itemCode'], $ObjectID['0']['_val']); //$field, $code, $val
                #$SQL.= $GBSQL . "<br>";
            }
            $output.= '<tr><td>' . Units_GetRealnameByName($GB_special['_name']) . ' (' . $GB_special['_name'] . ')</td><td>Is on the farm</td>';
            $output.= '<td>' . $Objectbuildings['0']['_obj'] . '<br>' . $ObjectID['0']['_val'] . '</td><td>' . $content . '</td></tr>';
        }
    } // for each special
    $output.= '</table><br>';
    //$output .= $SQL;
    return $output;
}
//------------------------------------------------------------------------------
//  Detect Collections and update the action list
//------------------------------------------------------------------------------
function GB_DetectCollections() {
    $GB_CollectionList = GB_GetCollectionList();
    if (!$GB_CollectionList) {
        GB_AddLog("GB Error: Collection list file missing");
        return;
    }
    foreach($GB_CollectionList as $value) {
        $GB_amount_Coll = count($value['collectable']);
        $i = 0;
        while ($i < $GB_amount_Coll) {
            $code = $value['collectable'][$i];
            GB_SQL_updAction("_collection", $code, 'Y');
            $i++;
        }
    }
}
function GB_getSQLsetting() {
    global $GBDBuser;
    $GBSQL = "SELECT _val,_set FROM gamesettings";
    $result = sqlite_query($GBDBuser, $GBSQL) or GB_AddLog("*** SQL Error *** " . $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GB_results = sqlite_fetch_all($result) or GB_AddLog("*** SQL Error *** " . $GBSQL);
        foreach($GB_results as $GB_result) {
            $GB_Setting[$GB_result['_set']] = $GB_result['_val'];
        }
    }
    return $GB_Setting;
}
function GBSQLError($GBDB, $GBSQL) {
    GB_AddLog("GB SQL warring *** GB if you do not see fail, than retry was good. ");
    sleep(1);
    $result = sqlite_query($GBDB, $GBSQL) or GBSQLError2($GBDB, $GBSQL);
    return $result;
}
function GBSQLError2($GBDB, $GBSQL) {
    GB_AddLog("GB SQL Error *** 2nd time failed. ");
    sleep(1);
    $result = sqlite_query($GBDB, $GBSQL) or GBSQLError3($GBDB, $GBSQL);
    return $result;
}
function GBSQLError3($GBDB, $GBSQL) {
    GB_AddLog("GB SQL Error *** 3rd time failed. try 1 more time ");
    sleep(1);
    $result = sqlite_query($GBDB, $GBSQL) or GB_AddLog("retry fail *** SQL: " . $GBSQL);
    return $result;
}
function GB_import_action($Task, $file) {
    //   $Task =  'ADD'   ==> Add items to Action DB
    //   $Task =  'SHOW' ==> show actions and return them.
    //   $Task =  'ERROR' ==> Check for Error's and return them.
    global $GBDBuser;
    global $this_plugin;
    $file = basename($file);
    $status = "";
    $show = "";
    //predefine inputs
    $VARY0 = array("Y", "0");
    $GB_imports = file($this_plugin['folder'] . '/actions/' . $file); //'plugins/GiftBox/actions.txt'
    if ($GB_imports) {
        $status.= '<br>';
        $status.= 'Import actions file found.<br>';
        foreach($GB_imports as $GB_import) {
            $skip = 'N';
            $error = '&nbsp;';
            $GB_import = explode(':', $GB_import, 15);
            if (strpos($GB_import['0'], '#') !== false) {
                $comment = $GB_import['0'];
                //echo 'Comment: ' .$comment . "<br>";

            } else {
                // let's fill the empty input
                $i = 0;
                while ($i < 14) {
                    if (!isset($GB_import[$i])) {
                        $GB_import[$i] = "";
                    }
                    $i++;
                }
                // detect the codes
                $code = $GB_import['0'];
                if (strlen($code) != 2) {
                    $skip = 'Y';
                    $error = " Code is not 2 digits";
                }
                $show.= '<tr><td>' . $code . '</td>';
                if ($Task == 'SHOW') {
                    $Unit = GBSQLGetUnitByCode($code);
                    $show.= '<td>' . $Unit['_name'] . '</td>';
                }
                $place = $GB_import['1'];
                if (!in_array($place, $VARY0)) {
                    $skip = 'Y';
                    $error = " Place is not Y or 0";
                }
                $show.= '<td>' . $place . '</td>';
                $sell = $GB_import['2'];
                if (!in_array($sell, $VARY0)) {
                    $skip = 'Y';
                    $error = " Sell is not Y or 0";
                }
                $show.= '<td>' . $sell . '</td>';
                $keep = $GB_import['3'];
                if (!is_numeric($keep)) {
                    $skip = 'Y';
                    $error = " Keep is not a number";
                }
                $show.= '<td>' . $keep . '</td>';
                $consume = $GB_import['4'];
                if (!in_array($consume, $VARY0)) {
                    $skip = 'Y';
                    $error = " Consume is not Y or 0";
                }
                $show.= '<td>' . $consume . '</td>';
                $a5 = $GB_import['5'];
                $a6 = $GB_import['6'];
                $a7 = $GB_import['7'];
                $a8 = $GB_import['8'];
                $a9 = $GB_import['9'];
                $a10 = $GB_import['10'];
                $a11 = $GB_import['11'];
                $a12 = $GB_import['12'];
                $note = $GB_import['13'];
                $show.= '<td>' . $error . '</td>';
                $show.= '<td>' . $note . '</td>';
                if ($skip == 'Y') {
                    $status.= 'Error detected in input ' . $code . ' Error:' . $error . '<br>';
                    $show.= '<td> This line will not be imported into the actions</td>';
                } else {
                    $show.= '<td> will be imported</td>';
                }
                if ($Task == 'ADD') { // we need to add the codes into the actions.
                    if ($skip == 'N') { // Code is good
                        $GBSQL = "INSERT OR REPLACE INTO action(_code,_place_on_farm,_selling,_keep,_consume) VALUES";
                        $GBSQL.= "('" . $code . "','" . $place . "','" . $sell . "','" . $keep . "','" . $consume . "')";
                        sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
                        //GB_AddLog ("SET: " . $GBSQL );

                    } // end skip

                } // end ADD

            } // not a comment

        } //foreach

    } else {
        $status.= "File Not found";
    }
    if ($Task == 'SHOW') {
        return $show;
    }
    return $status;
}
function GB_export_action($Task, $filename) {
    //   $Task =  'EXPORT' ==>
    //   $Task =  'SHOW'   ==>
    global $GBDBuser;
    $file = '##################################################################
#  export file
##################################################################
#  Format of the lines:
#Code
#| Place on farm? Y/0
#| | Sell? Y/0
#| | | Keep
#| | | | Consume? Y/0
#| | | | | reserved
#| | | | | | reserved
#| | | | | | | reserved
#| | | | | | | | reserved
#| | | | | | | | | reserved
#| | | | | | | | | | reserved
#| | | | | | | | | | | reserved
#0 1 2 3 4 5 6 7 8 9 0 | reserved
#| | | | | | | | | | | | |
';
    $screen = '';
    $screen.= '<table width="90%" class="sofT" cellspacing="0">';
    $screen.= '<tr><td class="helpHed">Code</td>
              <td class="helpHed">Item Name:</td>
              <td class="helpHed">Place?</td>
              <td class="helpHed">Sell?</td>
              <td class="helpHed">Keep?</td>
              <td class="helpHed">Consume?</td>
              <td class="helpHed">Notes:</td></tr>';
    $GBSQL = "SELECT * FROM action WHERE _target = '0' AND _place_in_special = '0' AND _construction = '0' AND _collection = '0'";
    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    $actions = sqlite_fetch_all($query);
    foreach($actions as $action) {
        $output = '<tr>';
        $output.= '<td>' . $action['_code'] . '</td>'; //  code
        $Unit = GBSQLGetUnitByCode($action['_code']);
        $output.= '<td>' . $Unit['_name'] . '</td>'; //  name
        $output.= '<td>' . $action['_place_on_farm'] . '</td>'; // place
        $output.= '<td>' . $action['_selling'] . '</td>'; // sell
        $output.= '<td>' . $action['_keep'] . '</td>'; // keep
        $output.= '<td>' . $action['_consume'] . '</td>'; // consume
        if ($action['_place_on_farm'] == '0' && $action['_selling'] == '0' && $action['_keep'] == '0' && $action['_consume'] == '0') {
            $output.= '<td>Will not be exported. NO action defined</td>';
        } else {
            $output.= '<td>Will be exported</td>';
            $file.= $action['_code'] . ':' . $action['_place_on_farm'] . ':' . $action['_selling'] . ':' . $action['_keep'] . ':' . $action['_consume'] . ':0:0:0:0:0:0:0:0:item name ' . $Unit['_name'] . ':
';
        }
        $output.= '</tr>'; // end
        $screen.= $output;
    }
    $screen.= '</table>';
    if ($Task == 'EXPORT') {
        $f = fopen($filename, "w+");
        fputs($f, $file, strlen($file));
        fclose($f);
        return 'done';
    }
    return $screen;
}
?>