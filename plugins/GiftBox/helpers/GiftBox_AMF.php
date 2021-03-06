<?php
//------------------------------------------------------------------------------
// Giftbox get from server SQL
//------------------------------------------------------------------------------
function GB_renew_giftbox_SQL() {
    DebugLog(" >> GB_renew_giftbox");
    global $GBox_Settings;
    global $GBDBuser;
    if (!$GBDBuser) {
        AddLog2('GB DB Not open let try again');
        $GBDBuser = GBDBuser_init();
    } else {
        AddLog2('GB DB already open ');
    }
    $GB_ingiftbox_temp = array();
    AddLog2("GB SQL Updating Giftbox...");
    $res = 0;
    global $need_reload;
    if ($need_reload) {
        $res = DoInit(); //reload farm
        $need_reload = false;
    }
    $vWorld = unserialize(file_get_contents(F('world.txt')));
    if (!isset($vWorld['data'][0])) {
        AddLog2("UP GB Error: BAD AMF - can not read the giftbox?");
        $res = "To many items?";
    }
    if (isset($vWorld['data'][0]['errorType']) && ($vWorld['data'][0]['errorType'] == 0)) {
        $res = 'OK';
    }
    // now let's get tthe giftbox
    $GB_ingiftbox_temp = $vWorld['data'][0]['data']['userInfo']['player']['storageData']['-1'];
    if (is_array($GB_ingiftbox_temp)) {
        // empty the giftbox
        $GBSQL = "DELETE FROM giftbox where _orig='GB'";
        sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        foreach($GB_ingiftbox_temp as $key => $giftboxItem) {
            $amount = Qs($giftboxItem[0]);
            $itemcode = Qs($key);
            $GBSQL = "INSERT INTO giftbox(_itemcode, _amount, _gifters, _orig )";
            $GBSQL.= " values($itemcode, $amount, '', 'GB')";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        AddLog2("GB SQL content Giftbox- saved in SQL");
    } else {
        AddLog2("GB SQL ERROR update =can not find giftbox=" . __LINE__);
    }
    // now look for ConsumableBox
    $GB_ingiftbox_temp = @$vWorld['data'][0]['data']['userInfo']['player']['storageData']['-6'];
    if (is_array($GB_ingiftbox_temp)) {
        // empty the giftbox
        $GBSQL = "DELETE FROM giftbox where _orig='CB'";
        sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        foreach($GB_ingiftbox_temp as $key => $giftboxItem) {
            $amount = Qs($giftboxItem[0]);
            $itemcode = Qs($key);
            $GBSQL = "INSERT INTO giftbox(_itemcode, _amount, _gifters, _orig )";
            $GBSQL.= " values($itemcode, $amount, '', 'CB')";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        AddLog2("GB SQL content ConsumableBox- saved in SQL");
    } else {
        AddLog2("GB SQL ERROR update =can not find giftbox=" . __LINE__);
    }
    // now let's get all storages
    $GB_AllStorages_temp = array();
    $GB_AllStorages_temp = @$vWorld['data'][0]['data']['userInfo']['player']['storageData'];
    if (is_array($GB_AllStorages_temp)) { // empty the all storages
        $GBSQL = "DELETE FROM totstorage";
        sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        sqlite_query($GBDBuser, 'BEGIN;');
        while (list($GB_storageCode, $stor_array) = each($GB_AllStorages_temp)) {
            $storagecode = Qs($GB_storageCode);
            foreach($stor_array as $key => $value) {
                $amount = Qs($value[0]);
                $itemcode = Qs($key);
                $GBSQL = "INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
                $GBSQL.= " values($storagecode, $itemcode, $amount, '')";
                sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            }
        }
        sqlite_query($GBDBuser, 'COMMIT;');
        AddLog2("GB SQL AllStorage update - saved ");
    } else {
        AddLog2("AllStorage ERROR - Not saved ");
    }
    // now add the storage data (cellar)
    $GB_ingiftbox_temp = @$vWorld['data'][0]['data']['userInfo']['player']['storageData']['-2'];
    if (is_array($GB_ingiftbox_temp)) {
        foreach($GB_ingiftbox_temp as $key => $giftboxItem) {
            $amount = Qs($giftboxItem[0]);
            $itemcode = Qs($key);
            $GBSQL = "INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
            $GBSQL.= " values('storage', $itemcode, $amount, '')";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        AddLog2("GB SQL content storage - saved in SQL");
    } else {
        AddLog2("GB SQL ERROR update =can not find storage =" . __LINE__);
    }
    // get the featureCredits (like valetine & pot of gold)
    $GB_featureCredits_temp = @$vWorld['data'][0]['data']['userInfo']['player']['featureCredits']['farm'];
    if (is_array($GB_featureCredits_temp)) {
        sqlite_query($GBDBuser, 'BEGIN;');
        foreach($GB_featureCredits_temp as $key => $value) {
            $storagecode = Qs($key);
            $current = Qs($value['current']);
            $received = Qs($value['received']);
            $GBSQL = "INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
            $GBSQL.= " values($storagecode, 'current', $current, '')";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            $GBSQL = "INSERT INTO totstorage(_storagecode, _itemcode, _amount, _gifters )";
            $GBSQL.= " values($storagecode, 'received', $received, '')";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        }
        sqlite_query($GBDBuser, 'COMMIT;');
        AddLog2("GB SQL featureCredits update - saved ");
    } else {
        AddLog2("featureCredits ERROR - Not saved ");
    }
    // Store all objects
    $objects = @$vWorld['data'][0]['data']['userInfo']['world']['objectsArray'];
    if (is_array($objects)) {
        $GBSQL = "DELETE FROM objects";
        sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        sqlite_query($GBDBuser, 'BEGIN;');
        foreach($objects as $id => $object) {
            $obj = Qs($id);
            $GBloop = array_keys($object);
            foreach($GBloop as $set1) {
                $set = Qs($set1);
                if (is_array($object[$set1])) { // handle the array like position & content
                    $val = Qs(serialize($object[$set1]));
                } else {
                    $val = Qs(addslashes($object[$set1]));
                }
                if ($set == "'message'") {
                    $val = "'message text'";
                }
                $GBSQL = "INSERT INTO objects(_obj, _set, _val) VALUES($obj, $set, $val)";
                //     AddLog2("GB SQL stor: " . $GBSQL);
                sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            }
        }
        sqlite_query($GBDBuser, 'COMMIT;');
        AddLog2("GB SQL objects update - saved ");
    } else {
        AddLog2("objects ERROR - Not saved ");
    }
    DebugLog(" << GB_renew_giftbox");
    return $res;
}
//------------------------------------------------------------------------------
// Giftbox get from server
//------------------------------------------------------------------------------
function GB_renew_giftbox() {
    DebugLog(" >> GB_renew_giftbox");
    global $GBox_Settings;
    $res = 0;
    global $need_reload;
    if ($need_reload) {
        $res = DoInit(); //reload farm
        $need_reload = false;
    }
    $amf2 = unserialize(file_get_contents(F('world.txt')));
    if (!isset($vWorld['data'][0])) {
        AddLog2("UP GB Error: BAD AMF - To many items on the farm?");
        $res = "To many items?";
    }
    if (isset($vWorld['data'][0]['errorType']) && ($vWorld['data'][0]['errorType'] == 0)) {
        $res = 'OK';
    }
    $GB_ingiftbox = @$vWorld['data'][0]['data']['userInfo']['player']['storageData']['-1'];
    if (is_array($GB_ingiftbox)) {
        save_botarray($GB_ingiftbox, F('ingiftbox.txt'));
        AddLog2("GiftBox content - saved ");
    } else {
        AddLog2("Giftbox ERROR update =can not find giftbox=" . __LINE__);
    }
    $GB_AllStorages = load_array(GBox_storage);
    if (!$GB_AllStorages) {
        $GB_AllStorages = array();
    }
    $GB_AllStorages['have'] = @$vWorld['data'][0]['data']['userInfo']['player']['storageData'];
    if (is_array($GB_AllStorages['have'])) {
        save_array($GB_AllStorages, GBox_storage);
        AddLog2("GiftBox AllStorage update - saved ");
    } else {
        AddLog2("AllStorage ERROR - Not saved ");
    }
    if ($GBox_Settings['Place']) {
        // see if we can store the latest info on farm XY
        $objects = @$vWorld['data'][0]['data']['userInfo']['world']['objectsArray'];
        save_array($objects, GBox_XY_objects);
        AddLog2("Giftbox Objects - saved ");
        // build the map to find empty spots
        GB_buildEmptyXY();
    }
    // now update the building parts
    GB_BuiltPartBD();
    DebugLog(" << GB_renew_giftbox");
    return $res;
}
//------------------------------------------------------------------------------
// Consume consume_kibble
//------------------------------------------------------------------------------
function GB_consumePet($targetObjetId, $ItemName) {
    DebugLog(" >> GB_consumeKibble");
    $res = 0;
    global $GB_tempid;
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $amf = CreateRequestAMF('use', 'WorldService.performAction');
    $i = 0;
    $amf->_bodys[0]->_value[1][$i]['params'][1]['id'] = $GB_tempid;
    $amf->_bodys[0]->_value[1][$i]['params'][1]['direction'] = 0;
    $amf->_bodys[0]->_value[1][$i]['params'][1]['itemName'] = $ItemName;
    $amf->_bodys[0]->_value[1][$i]['params'][1]['position'] = array('x' => 0, 'y' => 0, 'z' => 0);
    $amf->_bodys[0]->_value[1][$i]['params'][1]['deleted'] = false;
    $amf->_bodys[0]->_value[1][$i]['params'][1]['className'] = 'CPetsKibble';
    $amf->_bodys[0]->_value[1][$i]['params'][1]['tempId'] = - 1;
    $amf->_bodys[0]->_value[1][$i]['params'][2][0]['targetObjectId'] = $targetObjetId; //3215
    $amf->_bodys[0]->_value[1][$i]['params'][2][0]['targetUser'] = '0';
    $amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift'] = true;
    $amf->_bodys[0]->_value[1][$i]['params'][2][0]['isFree'] = false;
    $GB_tempid++;
    $res = RequestAMF($amf);
    AddLog2("Use $ItemName result: $res ");
    DebugLog(" << GB_ConsumeKibble");
    return $res;
}
//------------------------------------------------------------------------------
// Open Mystery gift & eggs
//------------------------------------------------------------------------------
function GB_OpenGift($ObjD, $GB_amount) {
    if($GB_amount>8) $GB_amount=8;
    DebugLog("Function >> " . __FUNCTION__);
    global $GB_tempid;
    if ($GB_tempid < 63000) $GB_tempid = 63000;
    $px_Setopts = LoadSavedSettings();
    if (!@$px_Setopts['bot_speed']) {
        $vSpeed = 1;
    }
    if (@$px_Setopts['bot_speed'] < 1) {
        $vSpeed = 1;
    } else {
        $vSpeed = $px_Setopts['bot_speed'];
    }
    if (@$px_Setopts['bot_speed'] > 8) {
        $vSpeed = 8;
    }
    $vRunMainLoop = ceil($GB_amount / $vSpeed);
    for ($vI = 0;$vI < $vRunMainLoop;$vI++) {
        $amf = new AMFObject("");
        $amf->_bodys[0] = new MessageBody();
        $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
        $amf->_bodys[0]->responseURI = '/1/onStatus';
        $amf->_bodys[0]->responseIndex = '/1';
        $amf->_bodys[0]->_value[0] = GetAMFHeaders();
        $vNumAction = 0;
        for ($vJ = ($vI * $vSpeed);(($vJ < (($vI * $vSpeed) + $vSpeed)) && ($vJ < $GB_amount));$vJ++) {
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = 'open';
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName'] = $ObjD['_name'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted'] = false;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className'] = $ObjD['_className'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id'] = $GB_tempid;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction'] = 0;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position'] = array('x' => 0, 'y' => 0, 'z' => 0);
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['state'] = 'static';
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId'] = - 1;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift'] = true;
            $amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = "WorldService.performAction";
            $amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
            $vNumAction++;
            $GB_tempid++;
        }
        $amf->_bodys[0]->_value[2] = 0;
        $res = RequestAMF($amf);
        if ($res === 'OK') {
            AddLog2("GB open " . $ObjD['_name'] . " result: $res [" . $GB_amount . " to go]");
            $need_reload = true;
        } else {
            AddLog2("Oeps " . $res);
            return ($res);
        }
    }
    DebugLog("Function << " . __FUNCTION__);
    return $res;
}
//------------------------------------------------------------------------------
// Check Cellar status.
//------------------------------------------------------------------------------
function GB_checkCellar() {
    global $GBDBmain;
    global $GBDBuser;
    global $GB_Setting;
    $TotCapacity = 0;
    $GB_Setting['StorageUsed'] = 0;
    $GB_Setting['StorageCapacity'] = 0;
    $GB_Setting['StorageContent'] = array();
    $GB_Setting['StorageLocation'] = 'N';
    //GB_AddLog("***************GB: test");
    //check if there is a cellar & what is the content.
    $GBSQL = "SELECT * FROM totstorage WHERE _storagecode = 'InventoryCellar' AND _itemcode = 'current'";
    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    if (sqlite_num_rows($query) > 0) {
        $totstorage = sqlite_fetch_all($query);
        $TotItems = $totstorage['0']['_amount'];
        if ($TotItems > 500) {
            $TotCapacity = 500;
        } else {
            $TotCapacity = $TotItems;
        }
    }
    GB_AddLog("GB cellar capacity: " . $TotCapacity);
    $GB_Setting['StorageCapacity'] = $TotCapacity;
    if ($TotCapacity > 0) {
        $GBSQL = "SELECT SUM(_amount) as total FROM totstorage WHERE _storagecode = 'storage'";
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        if (sqlite_num_rows($query) > 0) {
            $Result = sqlite_fetch_all($query);
            GB_AddLog("GB total in storage: " . $Result['0']['total']);
            $GB_Setting['StorageUsed'] = $Result['0']['total'];
            //$StorageContent = $Result['0'];
            //$myBushels = array_sum($bushels);

        }
        $GBSQL = "SELECT _itemcode,_amount FROM totstorage WHERE _storagecode = 'storage'";
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        if (sqlite_num_rows($query) > 0) {
            $Results = sqlite_fetch_all($query);
            $StorageContentTemp = array();
            foreach($Results as $Result) {
                $StorageContentTemp[$Result['_itemcode']] = $Result['_amount'];
            }
            $GB_Setting['StorageContent2'] = serialize($StorageContentTemp);
            $GB_Setting['StorageContent'] = serialize($Results);
        }
        $GBSQL = "SELECT _set,_val FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = 'hatchstorage')";
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
        while ($entry = sqlite_fetch_array($query, SQLITE_ASSOC)) {
            if ($entry['_set'] == 'position') {
                $GB_Setting['StorageLocation'] = $entry['_val'];
            }
        }
    }
}
//------------------------------------------------------------------------------
// Store item from Giftbox to the storage cellar.
//------------------------------------------------------------------------------
function GB_StoreCel($ObjD, $GB_amount) {
    DebugLog("Function >> " . __FUNCTION__);
    global $GB_tempid;
    global $GB_Setting;
    $px_time = time();
    $Cellar = unserialize($GB_Setting['StorageLocation']);
    if ($GB_tempid < 63000) $GB_tempid = 63000;
    $px_Setopts = LoadSavedSettings();
    if (!@$px_Setopts['bot_speed']) {
        $vSpeed = 1;
    }
    if (@$px_Setopts['bot_speed'] < 1) {
        $vSpeed = 1;
    } else {
        $vSpeed = $px_Setopts['bot_speed'];
    }
    if (@$px_Setopts['bot_speed'] > 8) {
        $vSpeed = 8;
    }
    $state = 'static';
    if ($ObjD['_className'] == 'Building') {
        $state = 'preview';
    }
    $vRunMainLoop = ceil($GB_amount / $vSpeed);
    for ($vI = 0;$vI < $vRunMainLoop;$vI++) {
        $amf = new AMFObject("");
        $amf->_bodys[0] = new MessageBody();
        $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
        $amf->_bodys[0]->responseURI = '/1/onStatus';
        $amf->_bodys[0]->responseIndex = '/1';
        $amf->_bodys[0]->_value[0] = GetAMFHeaders();
        $vNumAction = 0;
        for ($vJ = ($vI * $vSpeed);(($vJ < (($vI * $vSpeed) + $vSpeed)) && ($vJ < $GB_amount));$vJ++) {
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = 'store';
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName'] = $ObjD['_name'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id'] = $GB_tempid;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className'] = $ObjD['_className'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted'] = false;
            if ($ObjD['_className'] == 'Building') {
                $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['buildTime'] = 'NaN';
                $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['plantTime'] = "$px_time" . "321"; //1283025533719

            }
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['x'] = $Cellar['x'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['y'] = $Cellar['y'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position']['z'] = 0;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction'] = 0;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['state'] = $state;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId'] = - 1;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift'] = true;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['resource'] = 0;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['code'] = $ObjD['_code'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['origin'] = "-1";
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['target'] = - 2;
            $amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = "WorldService.performAction";
            $amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
            $vNumAction++;
            $GB_tempid++;
        }
        $amf->_bodys[0]->_value[2] = 0;
        $res = RequestAMF($amf);
        if ($res === 'OK') {
            AddLog2("GB store " . $ObjD['_name'] . " result: $res [" . $GB_amount . " to go]");
            $need_reload = true;
        } else {
            AddLog2("Oeps " . $res);
            return ($res);
        }
    }
    DebugLog("Function << " . __FUNCTION__);
    return $res;
}
//------------------------------------------------------------------------------
// Use consumable
//------------------------------------------------------------------------------
function GB_consume($ObjD, $GB_amount) {
    DebugLog(" >> GB_consume");
    global $GB_tempid;
    global $userId;
    if ($GB_tempid < 63000) $GB_tempid = 63000;
    $px_Setopts = LoadSavedSettings();
    if (!@$px_Setopts['bot_speed']) {
        $vSpeed = 1;
    }
    if (@$px_Setopts['bot_speed'] < 1) {
        $vSpeed = 1;
    } else {
        $vSpeed = $px_Setopts['bot_speed'];
    }
    if (@$px_Setopts['bot_speed'] > 20) {
        $vSpeed = 20;
    }
    $vRunMainLoop = ceil($GB_amount / $vSpeed);
    for ($vI = 0;$vI < $vRunMainLoop;$vI++) {
        $amf = new AMFObject("");
        $amf->_bodys[0] = new MessageBody();
        $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
        $amf->_bodys[0]->responseURI = '/1/onStatus';
        $amf->_bodys[0]->responseIndex = '/1';
        $amf->_bodys[0]->_value[0] = GetAMFHeaders();
        $vNumAction = 0;
        for ($vJ = ($vI * $vSpeed);(($vJ < (($vI * $vSpeed) + $vSpeed)) && ($vJ < $GB_amount));$vJ++) {
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = 'use';
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['id'] = $GB_tempid;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['direction'] = 0;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['itemName'] = $ObjD['_name'];
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['position'] = array('x' => 0, 'y' => 0, 'z' => 0);
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['deleted'] = false;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['className'] = 'Consumable';
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1]['tempId'] = - 1;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['targetUser'] = $userId;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isGift'] = true;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2][0]['isFree'] = false;
            $amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = "WorldService.performAction";
            $amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
            $vNumAction++;
            $GB_tempid++;
        }
        $amf->_bodys[0]->_value[2] = 0;
        $res = RequestAMF($amf);
        if ($res === 'OK') {
            AddLog2("GB consume " . $ObjD['name'] . " result: $res [" . $GB_amount . " to go]");
            $need_reload = true;
        } else {
            AddLog2("Oeps " . $res);
            return ($res);
        }
    }
    DebugLog(" << GB_Consume");
    return $res;
}
//------------------------------------------------------------------------------
//   addToInventory
//------------------------------------------------------------------------------
function GB_addToInventory($ObjD, $GB_amount) {
    DebugLog(" >> GB_addToInventory");
    $res = 0;
    global $GB_tempid;
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $i = 0;
    while ($GB_amount > 0) {
        $amf->_bodys[0]->_value[1][$i]['params'][0] = 'addToInventory';
        $amf->_bodys[0]->_value[1][$i]['params'][1]['id'] = $GB_tempid; //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['direction'] = 0; //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['state'] = $ObjD['state']; //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['itemName'] = $ObjD['name']; //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['position'] = array('x' => 47, 'y' => 63, 'z' => 0); //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['deleted'] = false; //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['className'] = $ObjD['className']; //
        $amf->_bodys[0]->_value[1][$i]['params'][1]['tempId'] = - 1; //
        $amf->_bodys[0]->_value[1][$i]['params'][2][0]['code'] = $ObjD['itemCode']; //
        $amf->_bodys[0]->_value[1][$i]['params'][2][0]['isGift'] = true; //
        $amf->_bodys[0]->_value[1][$i]['params'][2][0]['origin'] = "-1"; //
        $amf->_bodys[0]->_value[1][$i]['params'][2][0]['resource'] = 0;
        $amf->_bodys[0]->_value[1][$i]['sequence'] = GetSequense();;
        $amf->_bodys[0]->_value[1][$i]['functionName'] = "WorldService.performAction";
        $amf->_bodys[0]->_value[2] = 0;
        $GB_amount--;
        $GB_tempid++;
        $res = RequestAMF($amf);
        if ($res == "OK") {
            AddLog2("Into storage " . $ObjD['name'] . " result: $res [" . $GB_amount . " to go]");
        } else {
            AddLog2("Oeps " . $res);
            return;
        }
    }
    DebugLog(" << GB_addToInventory");
    return $res;
}
//------------------------------------------------------------------------------
// GB_HorseStable               WORK IN PROGRESS
//------------------------------------------------------------------------------
// class to find = HorseStableBuilding
function GB_HorseStable($ItemName, $ItemCode) {
    DebugLog(" >> GB_HorseStable");
    $res = 0;
    AddLog2("Building the horse stable with: " . $ItemName);
    $amf = CreateRequestAMF('', 'WorldService.performAction');
    $amf->_bodys[0]->_value[1][0]['params'][0] = $ItemCode;
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][0] = "Storage";
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][1] = "accessing_goods";
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][2] = "general_HUD_icon";
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][3] = "";
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][4] = "";
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][5] = "";
    $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][6] = "1";
    $amf->_bodys[0]->_value[1][2]['params'][0] = $ItemName;
    $res = RequestAMF($amf);
    if ($res == "OK") {
        AddLog2("result giftbox: $res");
    } else {
        AddLog2("Oeps " . $res);
        return;
    }
    DebugLog(" << GB_HorseStable");
    return $res;
}
//------------------------------------------------------------------------------
// TradeIn Collection
//------------------------------------------------------------------------------
function GB_TradeIn($CollectionID, $GB_amount) {
    DebugLog(" >> GB_TradeIn");
    $res = 0;
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $i = 0;
    while ($GB_amount > 0) {
        $amf->_bodys[0]->_value[1][$i]['functionName'] = "CollectionsService.onTradeIn";
        $amf->_bodys[0]->_value[1][$i]['params'][0] = $CollectionID; // like "C001"
        $amf->_bodys[0]->_value[1][$i]['sequence'] = GetSequense();;
        $amf->_bodys[0]->_value[2] = 0;
        $GB_amount--;
        $res = RequestAMF($amf);
        if ($res == "OK") {
            AddLog2("Trade in " . $CollectionID . " result: $res [" . $GB_amount . " to go]");
        } else {
            AddLog2("Oeps " . $res);
            return;
        }
    }
    DebugLog(" << GB_TradeIn");
    return $res;
}
//------------------------------------------------------------------------------
// Sell collectable out of gift box
//------------------------------------------------------------------------------
function GB_DoSellCol($ObjD, $GB_amount) {
    DebugLog(" >> GB_DoSellCol");
    if (array_key_exists('_className', $ObjD)) {
        $Class = $ObjD['_className'];
    } else {
        $Class = "";
    }
    unset($GLOBALS['amfphp']['encoding']);
    $px_Setopts = LoadSavedSettings();
    if (!@$px_Setopts['bot_speed']) {
        $vSpeed = 1;
    }
    if (@$px_Setopts['bot_speed'] < 1) {
        $vSpeed = 1;
    } else {
        $vSpeed = $px_Setopts['bot_speed'];
    }
    if (@$px_Setopts['bot_speed'] > 20) {
        $vSpeed = 20;
    }
    $vRunMainLoop = ceil($GB_amount / $vSpeed);
    for ($vI = 0;$vI < $vRunMainLoop;$vI++) {
        $amf = new AMFObject("");
        $amf->_bodys[0] = new MessageBody();
        $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
        $amf->_bodys[0]->responseURI = '/1/onStatus';
        $amf->_bodys[0]->responseIndex = '/1';
        $amf->_bodys[0]->_value[0] = GetAMFHeaders();
        $vNumAction = 0;
        for ($vJ = ($vI * $vSpeed);(($vJ < (($vI * $vSpeed) + $vSpeed)) && ($vJ < $GB_amount));$vJ++) {
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][0]['code'] = $ObjD['_code']; //added 2010-06-29
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = false;
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][2] = - 1;
            $amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = "UserService.sellStoredItem";
            $amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
            $vNumAction++;
        }
        $amf->_bodys[0]->_value[2] = 0;
        $res = RequestAMF($amf);
        AddLog2("GB: result $res");
        if ($res === 'OK') {
            $need_reload = true;
        } else {
            AddLog2("Oeps " . $res);
            $GLOBALS['amfphp']['encoding'] = 'amf3';
            return ($res);
        }
    }
    DebugLog(" << GB_DoSellCol");
    $GLOBALS['amfphp']['encoding'] = 'amf3';
    return $res;
}
//------------------------------------------------------------------------------
// Use fuel out of gift box
//------------------------------------------------------------------------------
function GB_BuyFuel($ObjD, $GB_amount) {
    DebugLog(" >> GB_BuyFuel");
    global $GB_tempid;
    if ($GB_tempid < 63000) $GB_tempid = 63000;
    $px_Setopts = LoadSavedSettings();
    if (!@$px_Setopts['bot_speed']) {
        $vSpeed = 1;
    }
    if (@$px_Setopts['bot_speed'] < 1) {
        $vSpeed = 1;
    } else {
        $vSpeed = $px_Setopts['bot_speed'];
    }
    if (@$px_Setopts['bot_speed'] > 20) {
        $vSpeed = 20;
    }
    $vRunMainLoop = ceil($GB_amount / $vSpeed);
    for ($vI = 0;$vI < $vRunMainLoop;$vI++) {
        $amf = new AMFObject("");
        $amf->_bodys[0] = new MessageBody();
        $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
        $amf->_bodys[0]->responseURI = '/1/onStatus';
        $amf->_bodys[0]->responseIndex = '/1';
        $amf->_bodys[0]->_value[0] = GetAMFHeaders();
        $vNumAction = 0;
        for ($vJ = ($vI * $vSpeed);(($vJ < (($vI * $vSpeed) + $vSpeed)) && ($vJ < $GB_amount));$vJ++) {
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][0] = $ObjD['_name']; //'fuel5';
            $amf->_bodys[0]->_value[1][$vNumAction]['params'][1] = true;
            $amf->_bodys[0]->_value[1][$vNumAction]['functionName'] = "FarmService.buyFuel";
            $amf->_bodys[0]->_value[1][$vNumAction]['sequence'] = GetSequense();
            $vNumAction++;
            $GB_tempid++;
        }
        $amf->_bodys[0]->_value[2] = 0;
        $res = RequestAMF($amf);
        if ($res === 'OK') {
            AddLog2("GB Use " . $ObjD['name'] . " result: $res [" . $GB_amount - $vJ . " to go]");
            $need_reload = true;
        } else {
            AddLog2("Oeps " . $res);
            return ($res);
        }
    }
    DebugLog(" << GB_BuyFuel");
    return $res;
}
//------------------------------------------------------------------------------
// Remove collectable out of gift box
//------------------------------------------------------------------------------
function GB_DoColAdd($ItemName, $ItemCode, $Amount_to_add) {
    DebugLog(" >> GB_DoColAdd");
    while ($Amount_to_add > 0) {
        $res = 0;
        //AddLog2("Accessing the giftbox for item " . $ItemName);
        $amf = CreateRequestAMF('', 'CollectionsService.addGiftItemToCollection');
        $amf->_bodys[0]->_value[1][0]['params'][0] = $ItemCode;
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][0] = "Storage";
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][1] = "accessing_goods";
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][2] = "general_HUD_icon";
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][3] = "";
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][4] = "";
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][5] = "";
        $amf->_bodys[0]->_value[1][1]['params'][0][0][0]['data'][6] = "1";
        $amf->_bodys[0]->_value[1][2]['params'][0] = $ItemName;
        $res = RequestAMF($amf);
        $Amount_to_add--;
        if ($res == "OK") {
            AddLog2($ItemName . "added to collection " . $Amount_to_add . " to go");
        } else {
            AddLog2("Oeps adding " . $ItemName . " to collection " . $res);
            return;
        }
    }
    DebugLog(" << GB_DoColAdd");
    return $res;
}
//------------------------------------------------------------------------------
// GB_storeItem from giftbox
//------------------------------------------------------------------------------
function GB_storeItem($Item, $Target) {
    DebugLog(" >> GB_storeItem");
    // Target stuff
    // What do we need Potofgold chateau
    $itemName = $Target['itemName']; // OK OK
    $direction = 0; // $Target['direction'] -- --
    $id = $Target['id']; // OK OK
    // $Target['contents'] =N need work
    if ($Target['contents'] == "N") {
        $contents = "";
    }
    if (is_array($Target['contents'])) {
        $contents = array();
        foreach($Target['contents'] as $value) {
            $contents[]['item'] = array('num' => $value['numItem']);
        }
    }
    $buildTime = 0; // $Target['buildTime'] -- --
    $positionx = $Target['position']['x']; // OK OK
    $positiony = $Target['position']['y']; // OK OK
    $state = $Target['state']; // OK OK
    $plantTime = 0; // $Target['plantTime'] =N =N
    $className = $Target['className']; // OK OK
    $paintColor = $Target['paintColor']; // ? OK
    // Item stuff
    $storedItemCode = $Item['_code']; // $Item['storedItemCode'] "code"
    $storedClassName = $Item['_className']; // $Item['storedClassName'] "class"
    $storedItemName = $Item['_name']; // $Item['storedItemName'] "name"
    $res = 0;
    //AddLog2("GiftBox Gold Gift: " . $ItemName);
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.performAction";
    $amf->_bodys[0]->_value[1][0]['params'][0] = "store"; //"storeItem";
    $amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $itemName;
    $amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = $direction;
    $amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $id;
    $amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = "NaN";
    $amf->_bodys[0]->_value[1][0]['params'][1]['contents'] = $contents;
    $amf->_bodys[0]->_value[1][0]['params'][1]['buildTime'] = $buildTime;
    $amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = 'false';
    $amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $positionx;
    $amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
    $amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $positiony;
    $amf->_bodys[0]->_value[1][0]['params'][1]['state'] = $state;
    $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime'] = $plantTime;
    $amf->_bodys[0]->_value[1][0]['params'][1]['className'] = $className;
    $amf->_bodys[0]->_value[1][0]['params'][2][0]['cameFromLocation'] = 0;
    $amf->_bodys[0]->_value[1][0]['params'][2][0]['storedItemCode'] = $storedItemCode;
    $amf->_bodys[0]->_value[1][0]['params'][2][0]['storedClassName'] = $storedClassName;
    $amf->_bodys[0]->_value[1][0]['params'][2][0]['resource'] = 0;
    $amf->_bodys[0]->_value[1][0]['params'][2][0]['storedItemName'] = $storedItemName;
    $amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
    $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
/*
    $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
    $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
    $amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
    $amf->_bodys[0]->_value[1][1]['params'][0] = "store"; //"storeItem";
    $amf->_bodys[0]->_value[1][1]['params'][1]['itemName'] = $itemName;
    $amf->_bodys[0]->_value[1][1]['params'][1]['direction'] = $direction;
    $amf->_bodys[0]->_value[1][1]['params'][1]['id'] = $id;
    $amf->_bodys[0]->_value[1][1]['params'][1]['tempId'] = "NaN";
    $amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = $contents;
    $amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = $buildTime;
    $amf->_bodys[0]->_value[1][1]['params'][1]['deleted'] = 'false';
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $positionx;
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $positiony;
    $amf->_bodys[0]->_value[1][1]['params'][1]['state'] = $state;
    $amf->_bodys[0]->_value[1][1]['params'][1]['plantTime'] = $plantTime;
    $amf->_bodys[0]->_value[1][1]['params'][1]['className'] = $className;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] = $storedItemCode;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = $storedClassName;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] = $storedItemName;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] = true;
    $amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
*/
    $amf->_bodys[0]->_value[2] = 0;
    $res = RequestAMF($amf);
    if ($res == "OK") {
        AddLog2("StoreItem: $storedItemName in $itemName Result: $res");
    } else {
        AddLog2("Oeps handeling " . $ItemName . " - " . $res);
        return;
    }
    DebugLog(" << GB_storeItem");
    return $res;
}
//------------------------------------------------------------------------------
// GB_storeItem2 from giftbox
//------------------------------------------------------------------------------
function GB_storeItem2($Item, $Target, $GB_amount) {
    DebugLog(" >> GB_storeItem2");
    // Target stuff
    // What do we need Potofgold chateau
    $itemName = $Target['itemName']; // OK OK
    $direction = 0; // $Target['direction'] -- --
    $id = $Target['id']; // OK OK
    // $Target['contents'] =N need work
    $contents = array();
    if ($Target['contents'] == "N") {
        $contents = "";
    }
    if (is_array($Target['contents'])) {
        $contents = array();
        foreach($Target['contents'] as $value) {
            $contents[]['item'] = array('num' => $value['numItem']);
        }
    }
    $buildTime = 0; // $Target['buildTime'] -- --
    $positionx = $Target['position']['x']; // OK OK
    $positiony = $Target['position']['y']; // OK OK
    $state = $Target['state']; // OK OK
    $plantTime = 0; // $Target['plantTime'] =N =N
    $className = $Target['className']; // OK OK
    $paintColor = $Target['paintColor']; // ? OK
    // Item stuff
    //if($Item['code'] == 1) $Item['code'] = "Lb"; // fix for olives
    $storedItemCode = $Item['_code']; // $Item['storedItemCode'] "code"
    $storedClassName = $Item['_className']; // $Item['storedClassName'] "class"
    $storedItemName = $Item['_name']; // $Item['storedItemName'] "name"
    //  $storedItemCode = $Item['code'];    // $Item['storedItemCode'] "code"
    //  $storedClassName = $Item['class'];  // $Item['storedClassName'] "class"
    //  $storedItemName = $Item['name'];    // $Item['storedItemName'] "name"
    $res = 0;
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    while ($GB_amount > 0) {
        $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
        $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
        $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
        $amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
        $amf->_bodys[0]->_value[1][1]['params'][0] = "store"; //2010-07-01 "storeItem";
        $amf->_bodys[0]->_value[1][1]['params'][1]['itemName'] = $itemName;
        $amf->_bodys[0]->_value[1][1]['params'][1]['direction'] = $direction;
        $amf->_bodys[0]->_value[1][1]['params'][1]['id'] = $id;
        $amf->_bodys[0]->_value[1][1]['params'][1]['tempId'] = "NaN";
        $amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = $contents;
        $amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = $buildTime;
        $amf->_bodys[0]->_value[1][1]['params'][1]['deleted'] = 'false';
        $amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $positionx;
        $amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0;
        $amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $positiony;
        $amf->_bodys[0]->_value[1][1]['params'][1]['state'] = $state;
        $amf->_bodys[0]->_value[1][1]['params'][1]['plantTime'] = $plantTime;
        $amf->_bodys[0]->_value[1][1]['params'][1]['className'] = $className;
        $amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] = 0;
        $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] = $storedItemCode;
        $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = $storedClassName;
        $amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
        $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] = $storedItemName;
        $amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] = true;
        $amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
        $amf->_bodys[0]->_value[2] = 0;
        $GB_amount--;
        $res = RequestAMF($amf);
        if ($res == "OK") {
            AddLog2("StoreItem: $itemName Result: $res");
        } else {
            AddLog2("Oeps handeling " . $ItemName . " - " . $res);
            return;
        }
        if ($res != "OK") {
            GB_AMF_Error($res);
            return "Fialed";
        }
    } // while
    DebugLog(" << GB_storeItem");
    return $res;
}
//------------------------------------------------------------------------------
// Remove Valentines Gift out of gift box
//------------------------------------------------------------------------------
function GB_DoValentin($ItemCode, $ItemName, $ValBoxObj) {
    DebugLog(" >> GB_DoValentin");
    //load settings
    $res = 0;
    AddLog2("Accessing the giftbox for Valentines Gift: " . $ItemName);
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
    $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
    $amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
    $amf->_bodys[0]->_value[1][1]['params'][0] = "storeItem";
    $amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['className'] = 'ValentinesBox';
    $amf->_bodys[0]->_value[1][1]['params'][1]['state'] = 'built';
    $amf->_bodys[0]->_value[1][1]['params'][1]['deleted'] = 'false';
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $ValBoxObj['0']['position']['x'];
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $ValBoxObj['0']['position']['y'];
    //   $amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] =
    //   $amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] =
    $amf->_bodys[0]->_value[1][1]['params'][1]['tempId'] = "NaN";
    $amf->_bodys[0]->_value[1][1]['params'][1]['plantTime'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['itemName'] = "valentinesbox";
    $amf->_bodys[0]->_value[1][1]['params'][1]['direction'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['id'] = $ValBoxObj['0']['id'];
    $amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = "";
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] = $ItemCode; //8D
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = "ValentinesGift";
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] = $ItemName; //  valentine_03
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] = true;
    $amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
    $amf->_bodys[0]->_value[2] = 0;
    $res = RequestAMF($amf);
    if ($res == "OK") {
        AddLog2("result Valentines Gift: $res");
    } else {
        AddLog2("Oeps handeling " . $ItemName . " - " . $res);
        return;
    }
    DebugLog(" << GB_DoValentin");
    return $res;
}
//------------------------------------------------------------------------------
// Remove Gold out of gift box
//------------------------------------------------------------------------------
function GB_DoGold($ItemCode, $ItemName, $potofgold) {
    DebugLog(" >> GB_DoGold");
    $res = 0;
    AddLog2("GiftBox Gold Gift: " . $ItemName);
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
    $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
    $amf->_bodys[0]->_value[1][1]['functionName'] = "WorldService.performAction";
    $amf->_bodys[0]->_value[1][1]['params'][0] = "storeItem";
    $amf->_bodys[0]->_value[1][1]['params'][1]['itemName'] = "potofgold";
    $amf->_bodys[0]->_value[1][1]['params'][1]['direction'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['id'] = $potofgold['0']['id'];
    $amf->_bodys[0]->_value[1][1]['params'][1]['tempId'] = "NaN";
    $amf->_bodys[0]->_value[1][1]['params'][1]['contents'] = "";
    $amf->_bodys[0]->_value[1][1]['params'][1]['buildTime'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['deleted'] = 'false';
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['x'] = $potofgold['0']['position']['x'];
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['z'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['position']['y'] = $potofgold['0']['position']['y'];
    $amf->_bodys[0]->_value[1][1]['params'][1]['state'] = 'built';
    $amf->_bodys[0]->_value[1][1]['params'][1]['plantTime'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][1]['className'] = 'PotOfGold';
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['cameFromLocation'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemCode'] = $ItemCode;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedClassName'] = "PotOfGoldItem";
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['resource'] = 0;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['storedItemName'] = $ItemName;
    $amf->_bodys[0]->_value[1][1]['params'][2][0]['isGift'] = true;
    $amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
    $amf->_bodys[0]->_value[2] = 0;
    $res = RequestAMF($amf);
    if ($res == "OK") {
        AddLog2("Result Gold: $res");
    } else {
        AddLog2("Oeps handeling " . $ItemName . " - " . $res);
        return;
    }
    DebugLog(" << GB_DoGold");
    return $res;
}
//########################################################################
// Place item on the farm.
//########################################################################
function GB_PlaceM($ObjD, $GB_amount) {
    global $GB_tempid;
    global $GBox_Settings;
    AddLog2('GiftBox Place item(s) start.. ');
    $res = 0;
    $px_time = time();
    if (array_key_exists('type', $ObjD)) {
        $state = "static";
        if ($ObjD['type'] == "Decoration") {
            $state = "static";
        }
        if ($ObjD['type'] == "RotateableDecoration") {
            $state = "horizontal";
        }
        if ($ObjD['type'] == "animal") {
            $state = "bare";
        }
    } else {
        $state = "static";
    }
    $type = "bla";
    if ($ObjD['type'] == "animal") {
        $type = "Animal";
    }
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    while ($GB_amount > 0) {
        $EmptyXY = EmptyXY();
        $amf->_bodys[0]->_value[1][0]['params'][0] = 'place';
        $amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
        $amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 1;
        if (array_key_exists('growTime', $ObjD)) {
            $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime'] = $px_time . "123";
        }
        $amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = - 1;
        $amf->_bodys[0]->_value[1][0]['params'][1]['className'] = $type;
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $EmptyXY['x'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $EmptyXY['y'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
        $amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $ObjD['name'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['state'] = $state;
        $amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
        $amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal'] = - 1;
        $amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
        $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
        $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.performAction";
        $amf->_bodys[0]->_value[2] = 0;
        $GB_tempid++;
        $GB_amount--;
        $res = RequestAMF($amf);
        AddLog2("Giftbox placed: " . $ObjD['name'] . " Result: $res [" . $GB_amount . " to go]. ");
        //if($GBox_Settings['debug'])
        if ($GB_Setting['DoDebug']) AddLog2("Giftbox placed On: " . $EmptyXY['x'] . "-" . $EmptyXY['y'] . ' tempid:' . $GB_tempid);
    } // end while
    $amf2 = RequestAMFIntern($amf);
    if (!isset($amf2->_bodys[0]->_value['data'][0])) {
        AddLog2("UP GB Error: BAD AMF - To many items on the farm?");
        $res = "To many on the farm?";
    }
    if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0)) {
        $res = 'OK';
    }
    if ($res == 'OK') {
        if ($GB_Setting['DoDebug']) AddLog2("GiftBox place item(s) done");
    } else {
        AddLog2("GiftBox place ERROR ");
    }
    return $res;
}
//########################################################################
// Place item on the farm.
//########################################################################
function GB_PlaceM2($ObjD, $GB_amount, $loc) {
    global $GB_tempid;
    global $GBox_Settings;
    AddLog2('GiftBox Place item(s) start.. ');
    $res = 0;
    $px_time = time();
    if (array_key_exists('_type', $ObjD)) {
        $state = "static";
        if ($ObjD['_type'] == "Decoration") {
            $state = "static";
        }
        if ($ObjD['_type'] == "RotateableDecoration") {
            $state = "horizontal";
        }
        if ($ObjD['_type'] == "animal") {
            $state = "bare";
        }
    } else {
        $state = "static";
    }
    $type = "bla";
    if ($ObjD['_type'] == "animal") {
        $type = "Animal";
    }
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    while ($GB_amount > 0) {
        $EmptyXY = TEmptyXY($loc, "ONE");
        if ($EmptyXY == "fail") {
            AddLog2("Error: No more locations left for " . $loc);
            return "fail";
        }
        $amf->_bodys[0]->_value[1][0]['params'][0] = 'place';
        $amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
        $amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 1;
        if (array_key_exists('growTime', $ObjD)) {
            $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime'] = $px_time . "123";
        }
        $amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = - 1;
        $amf->_bodys[0]->_value[1][0]['params'][1]['className'] = $type;
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $EmptyXY['x'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $EmptyXY['y'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
        $amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $ObjD['name'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['state'] = $state;
        $amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
        $amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal'] = - 1;
        $amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
        $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
        $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.performAction";
        $amf->_bodys[0]->_value[2] = 0;
        $GB_tempid++;
        $GB_amount--;
        $res = RequestAMF($amf);
        AddLog2("Giftbox placed: " . $ObjD['realname'] . " Result: $res [" . $GB_amount . " to go]. ");
        //if($GBox_Settings['debug'])
        if ($GB_Setting['DoDebug']) AddLog2("Giftbox placed On: " . $EmptyXY['x'] . "-" . $EmptyXY['y'] . ' tempid:' . $GB_tempid);
    } // end while
    if (!isset($amf2->_bodys[0]->_value['data'][0])) {
        AddLog2("UP GB Error: BAD AMF - To many itmes on the farm?");
        $res = "To many items on the farm?";
    }
    if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0)) {
        $res = 'OK';
    }
    if ($res == 'OK') {
        if ($GB_Setting['DoDebug']) AddLog2("GiftBox place item(s) done");
    } else {
        AddLog2("GiftBox place ERROR ");
    }
    return $res;
}
//########################################################################
// Place item on the farm.  Version 3
//########################################################################
function GB_PlaceM3($ObjD, $GB_amount, $loc) {
    global $GB_tempid;
    //    global $GBox_Settings  ;
    global $GB_Setting;
    AddLog2('GiftBox Place item(s) start.. ');
    // Get all settings correct
    $res = 0;
    $px_time = time();
    if (array_key_exists('_type', $ObjD)) {
        $state = "static";
        if ($ObjD['_type'] == "Decoration") {
            $state = "static";
        }
        if ($ObjD['_type'] == "RotateableDecoration") {
            $state = "horizontal";
        }
        if ($ObjD['_type'] == "animal") {
            $state = "bare";
        }
    } else {
        $state = "static";
    }
    $type = $ObjD['_type'];
    if ($ObjD['_type'] == "animal") {
        $type = "Animal";
    }
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    while ($GB_amount > 0) {
        $EmptyXY = TEmptyXY3($loc, "ONE");
        if ($EmptyXY == "fail") {
            AddLog2("Error: No more locations left for " . $loc);
            return "fail";
        }
        $amf->_bodys[0]->_value[1][0]['params'][0] = 'place';
        $amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
        $amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 1;
        if (array_key_exists('growTime', $ObjD)) {
            $amf->_bodys[0]->_value[1][0]['params'][1]['plantTime'] = $px_time . "123";
        }
        $amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = - 1;
        $amf->_bodys[0]->_value[1][0]['params'][1]['className'] = $type;
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['x'] = $EmptyXY['x'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['y'] = $EmptyXY['y'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['position']['z'] = 0;
        $amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $ObjD['_name'];
        $amf->_bodys[0]->_value[1][0]['params'][1]['state'] = $state;
        $amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $GB_tempid;
        $amf->_bodys[0]->_value[1][0]['params'][2][0]['isStorageWithdrawal'] = - 1;
        $amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = true;
        $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
        $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.performAction";
        $amf->_bodys[0]->_value[2] = 0;
        $GB_tempid++;
        $GB_amount--;
        $res = RequestAMF($amf);
        if ($res == 'OK') {
            AddLog2("Giftbox placed: " . $ObjD['realname'] . " Result: $res [" . $GB_amount . " to go]. ");
            if ($GB_Setting['DoDebug']) AddLog2("GiftBox place item(s) X: " . $EmptyXY['x'] . " Y:" . $EmptyXY['y']);
        } else {
            AddLog2("GiftBox place ERROR X: " . $EmptyXY['x'] . " Y:" . $EmptyXY['y']);
        }
        //if($GBox_Settings['debug'])
        //$debug = "1";
        //if($debug) AddLog2("Giftbox placed On: " . $EmptyXY['x'] . "-". $EmptyXY['y'] . ' tempid:' . $GB_tempid);

    } // end while
    if ($res == 'OK') {
        if ($GB_Setting['DoDebug']) AddLog2("GiftBox place item(s) done");
    } else {
        AddLog2("GiftBox place ERROR ");
    }
    return $res;
}
// ------------------------------------------------------------------------------
// RequestAMF sends AMF request to the farmville server
//  @param object $request AMF request
//  @return string If the function succeeds, the return value is 'OK'. If the
// function fails, the return value is error string
// ------------------------------------------------------------------------------
function RequestAMF2($amf) {
    DebugLog(" >> RequestAMF2");
    return RequestAMF($amf);
}
//------------------------------------------------------------------------------
// add vehiclePart to vehicle in storage
//------------------------------------------------------------------------------
function GB_DoGarage($ItemCode, $garage) {
    DebugLog(" >> GB_DoGarage");
    $res = 0;
    $garageId = $garage;
    $ItemString = $ItemCode['itemCode'] . ":" . $ItemCode['numParts'];
    //AddLog2("GB Garage " );
    $GB_test_time = time();
    $amf = new AMFObject("");
    $amf->_bodys[0] = new MessageBody();
    $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
    $amf->_bodys[0]->responseURI = '/1/onStatus';
    $amf->_bodys[0]->responseIndex = '/1';
    $amf->_bodys[0]->_value[0] = GetAMFHeaders();
    $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.sendStats";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['statfunction'] = "count";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][0] = "Storage";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][1] = "accessing_goods";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][2] = "general_HUD_icon";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][3] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][4] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][5] = "";
    $amf->_bodys[0]->_value[1][0]['params'][0][0][0]['data'][6] = 1;
    $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
    $amf->_bodys[0]->_value[1][1]['functionName'] = "EquipmentWorldService.onAddPartToEquipmentInGarage"; //ok
    $amf->_bodys[0]->_value[1][1]['params'][0] = $garageId; // the id
    $amf->_bodys[0]->_value[1][1]['params'][1] = $ItemString; // like V1:1
    $amf->_bodys[0]->_value[1][1]['params'][2] = true;
    $amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense(); //ok
    $amf->_bodys[0]->_value[2] = 0;
    $res = RequestAMF($amf);
    if ($res == "OK") {
        //AddLog2("Result garage: $res");

    } else {
        AddLog2("Oeps handeling " . $ItemName . " - " . $res);
        return;
    }
    DebugLog(" << GB_DoGarage");
    return $res;
}
?>