<?php
//------------------------------------------------------------------------------
// Giftbox main function in hook
//------------------------------------------------------------------------------
function Giftbox() {
    AddLog2('GB Hook start..');
    $T = time(true);
    // begin SQL setup
    global $GBDBmain;
    global $GBDBuser;
    GBDBmain_init("Hook");
    GBDBuser_init("Hook");
    //GB_get_World_storge_xml_SQL();
    GB_renew_giftbox_SQL();
    // end SQL setup
    // Get the settings
    global $GB_Setting;
    $GBSQL = "SELECT _val,_set FROM gamesettings";
    $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    if (sqlite_num_rows($result) > 0) {
        $GB_results = sqlite_fetch_all($result) or GBSQLError($GBDBuser, $GBSQL);
        foreach($GB_results as $GB_result) {
            $GB_Setting[$GB_result['_set']] = $GB_result['_val'];
        }
    }
    // import default actions?
    GB_AutoActionFile();
    // if place is on, than let's update the locations to find empty locations
    if ($GB_Setting['DoPlace']) {
        GBCreateMap();
    }
    if ($GB_Setting['DoDebug']) {
        GB_AddLog("DoDebug: YES");
    }
    AddLog2('GB Detecting building parts.');
    GB_DetectBuildingParts4();
    //new Building parts stuff 2010-07-14
    AddLog2('GB Detecting building parts - new version.');
    GB_BuildingParts4();
    if ($GB_Setting['RunPlugin']) {
        GB_AddLog("Giftbox loading...");
        //check the cellar for storage.
        if ($GB_Setting['DoStorage']) {
            GB_checkCellar();
        }
        // check this amount of items in giftbox.
        $result1 = sqlite_query($GBDBuser, "SELECT SUM(_amount) FROM giftbox");
        if (sqlite_num_rows($result1) > 0) {
            $GB_total_in_giftbox = sqlite_fetch_single($result1);
        } else {
            $GB_total_in_giftbox = 0;
        }
        GB_AddLog("GB items counted: " . $GB_total_in_giftbox);
        global $is_debug;
        global $GB_tempid;
        if ($GB_tempid == "") $GB_tempid = 63000;
        //get the collection info.
        $GBccount = array();
        $GBccount = GB_LoadCcount();
        $GB_changed = false; //true when we did action.
        $MAP_ObjectArray = array();
        $Map_all_items = array();
        $MapXY = array();
        $EmptyXY = array();
        GB_AddLog("Looking into giftbox...");
        if ($GB_Setting['DoFeetPet']) {
            GB_AddLog("GB: detecting Pet(s)...");
            $GB_Pets = GB_FindPetsSQL();
            $found = count(array_keys($GB_Pets));
            GB_AddLog("GiftBox: found $found pet(s).");
            if ($found > 0) {
                foreach($GB_Pets as $GB_Petfeed) {
                    if ($GB_Petfeed['isRunAway'] == 1) {
                        GB_AddLog($GB_Petfeed['petName'] . ' is run away ');
                    } else {
                        $FeedWhat = $GB_Petfeed['FeedWhat'];
                        $FeedName = $GB_Petfeed['petName'];
                        GB_AddLog($FeedName . ' needs ' . $FeedWhat . ' ' . nicetime($GB_Petfeed['feedtime']));
                    }
                }
            }
        } // DoFeetPet
        // load the totstorage.
        $GBSQL = "SELECT * FROM totstorage ";
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        $totstorages = sqlite_fetch_all($query);
        if (!is_array($totstorages)) {
            $totstorages = array();
        }
        // load the giftbox...
        $GBSQL = "SELECT * FROM giftbox ";
        $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        $giftboxs = sqlite_fetch_all($query);
        foreach($giftboxs as $giftbox) {
            $GB_ItemCode = $giftbox["_itemcode"];
            $GB_ItemAmount = $giftbox["_amount"];
            // if items = 0 then skip this item.
            if ($GB_ItemAmount < 1) {
                continue;
            }
            $place_on_farm = '';
            $place_in_build = '';
            $place_in_amount = '';
            $place_in_max = '';
            $place_in_special = '';
            $target = '';
            $selling = '';
            $keep = 0;
            $construction = '';
            $collection = '';
            $consume = '';
            $vNoLimit='off';
            //  Let's check if there is action for this item.
            $GBSQL = "SELECT * FROM action WHERE _code = '" . $GB_ItemCode . "'";
            $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            $action = sqlite_fetch_all($query);
            if (!empty($action)) { // so there is action.
                $place_on_farm = $action['0']['_place_on_farm'];
                $place_in_build = $action['0']['_place_in_build'];
                $place_in_amount = $action['0']['_place_in_amount'];
                $place_in_max = $action['0']['_place_in_max'];
                $place_in_special = $action['0']['_place_in_special'];
                $target = $action['0']['_target'];
                $selling = $action['0']['_selling'];
                $construction = $action['0']['_construction'];
                $keep = $action['0']['_keep'];
                $collection = $action['0']['_collection'];
                $consume = $action['0']['_consume'];
                //GB_AddLog ("GB action found  for: " . $GB_ItemCode . " place_in_special: " . $place_in_special . " T:". $target . " DoSpec:" . $GB_Setting['DoSpecials']);

            }
            // prepare Unit settings
            $GBSQL = "SELECT * FROM units WHERE _code = '" . $GB_ItemCode . "' ";
            $query = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
            $Unit = sqlite_fetch_all($query);
            // Get the realname
            $Unit['0']['realname'] = GB_get_friendlyName($Unit['0']['_name']);
            // check if we need to feed pet
            $FeedNowCheck = false;
            $PetFeedFound = "";
            if ($GB_ItemCode == "0O") {
                $FeedNowCheck = true;
                $PetFeedFound = "Puppy Kibble ";
            }
            if ($GB_ItemCode == "0z") {
                $FeedNowCheck = true;
                $PetFeedFound = "Dog Treat ";
            }
            if ($FeedNowCheck == true && $GB_Setting['DoFeetPet']) {
                GB_AddLog($GB_ItemAmount . " " . $PetFeedFound . " found. Checking if needed.");
                if ($GB_ItemAmount > 0) { // loop all pets
                    foreach($GB_Pets as $GB_Petfeed) {
                        if ($GB_Petfeed['isRunAway'] == 1) {
                            GB_AddLog($GB_Petfeed['petName'] . ' is run away can not feed');
                        } else {
                            $FeedWhat = $GB_Petfeed['FeedWhat'];
                            $FeedName = $GB_Petfeed['petName'];
                            $FeedNow = true;
                            if ($FeedWhat == "kibble" && $GB_ItemCode == "0z") {
                                GB_AddLog("This Puppy does not need Dog Treats..");
                                $FeedNow = false;
                            }
                            if ($FeedWhat == "treat" && $GB_ItemCode == "0O") {
                                GB_AddLog("This Dog does not need Kibble..");
                                $FeedNow = false;
                            }
                            if ($FeedNow == true) {
                                $UnixNow = time();
                                if ($UnixNow > $GB_Petfeed['feedtime']) { // need feed now
                                    $FeedWhat = $GB_Petfeed['FeedWhat'];
                                    GB_AddLog("Feeding " . $GB_Petfeed['petName'] . " " . $FeedWhat);
                                    $result = GB_consumePet($GB_Petfeed['id'], "consume_" . $FeedWhat);
                                    GB_AddLog("result: " . $result);
                                    if ($result == "OK") {
                                        GB_AddLog("Kibble feeded ok");
                                        $GB_changed = true; // giftbox changed
                                        GB_Stat3($GB_ItemCode, "Pet feed", $GB_ItemAmount, "Pet Feed");
                                        //GB_Stat($GB_ItemCode, "Kibble feed", 0, 0, $GB_ItemAmount,0 );

                                    } else {
                                        GB_AddLog("GB Need to reload");
                                        $giftboxs = array();
                                        break;
                                    }
                                } else {
                                    GB_AddLog($GB_Petfeed['petName'] . ' need to be feed ' . nicetime($GB_Petfeed['feedtime']));
                                }
                            }
                        }
                    }
                }
            } // End pet feet.
            // check target settings global.
            if ($target != 0) {
                $Target = GBSQLGetObjByID($target);
                $TotItems = 0;
                $TargetItemHave = 0;
                // map the content of the target and find the total items have
                if (is_array($Target['contents'])) { // count the contents
                    foreach($Target['contents'] as $content) {
                        $TotItems = $TotItems + $content['numItem'];
                        $TargetCont[$content['itemCode']] = $content['numItem'];
                        if ($GB_ItemCode == $content['itemCode']) {
                            $TargetItemHave = $content['numItem'];
                        }
                    }
                } // end contents
                // now check if the item is in totstorage.
                $featureCreditsName = 'N';
                if ($Target['itemName'] == 'valentinesbox') {
                    $featureCreditsName = 'valentine';
                    $vNoLimit='on';
                }
                if ($Target['itemName'] == 'potofgold') {
                    $featureCreditsName = 'potOfGold';
                    $vNoLimit='on';
                }
                if ($Target['itemName'] == 'easterbasket') {
                    $featureCreditsName = 'easterBasket';
                    $vNoLimit='on';
                }
                if ($Target['itemName'] == 'wedding') {
                    $featureCreditsName = 'tuscanWedding';
                    $vNoLimit='on';
                }
                if ($Target['itemName'] == 'beehive_finished') {
                    $featureCreditsName = 'beehive';
                }
                if ($Target['itemName'] == 'hatchstorage') {
                    $featureCreditsName = 'InventoryCellar';
                    $vNoLimit='on';
                }
                if ($Target['itemName'] == 'animalfeedtrough') {
                    $featureCreditsName = 'animalFeedTrough';
                }
                if ($Target['itemName'] == 'halloweenbasket') {
                    $featureCreditsName = 'halloweenBasket';
                    $vNoLimit='on';
                }
                if ($featureCreditsName != 'N') {
                    $GBSQL = "SELECT _amount FROM totstorage WHERE _storagecode = '" . $featureCreditsName . "' AND _itemcode = 'current'";

                    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
                    $TotItems = sqlite_fetch_single($query);
#                    if (sqlite_num_rows($query) > 0) {
#                        $totstorage = sqlite_fetch_all($query);
#                        $TotItems = $totstorages['0']['_amount'];
#                    }
                }
                // Get target unit details.
                $GBSQL = "SELECT * FROM units WHERE _name = '" . $Target['itemName'] . "' ";
                $result = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
                $TargetUnit = sqlite_fetch_all($result);
                // check capacity of target
                $TargetCapacity = 0;
                //GB_AddLog("Target Capacity log: A " . $TargetCapacity);
                if (!array_key_exists('isFullyBuilt', $Target)) {
                    $Target['isFullyBuilt'] = "N";
                }
                if ($Target['isFullyBuilt'] == "1") {
                    $TargetCapacity = $TargetUnit['0']['_capacity'];
                    //  GB_AddLog("Target Capacity log: B " . $TargetCapacity);
                    if (array_key_exists('expansionLevel', $Target)) {
                        $level = $Target['expansionLevel'];
                        if ($level > 1) {
                            $GBSQL = "SELECT _capacity FROM unitbuilding WHERE _level = '" . $level . "' AND _buildingcode = '" . $TargetUnit['0']['_code'] . "' ";
                            $result = sqlite_query($GBDBmain, $GBSQL);
                            $TargetCapacity = sqlite_fetch_single($result);
                        }
                    } else {
                        $level = 0;
                    }
                } // end fully build
                //  GB_AddLog("Target Capacity log: C " . $TargetCapacity);
                // check if building is in construction
                $TargetIsConstruction = 'N';
                if ($Target['state'] == 'construction') {
                    $TargetIsConstruction = 'Y';
                } else { // check if it is a horsestable
                    if ($Target['itemName'] == 'horsestablewhite') { // check if horse stable has expansionParts
                        if (count(array_keys($Target['expansionParts'])) > 0) { // yes, we have  expansionParts
                            $TargetIsConstruction = 'Y';
                        }
                        if ($TargetItemHave > 0) { // yes, we have  expansionParts
                            $TargetIsConstruction = 'Y';
                        }
                    }
                }
                //GB_AddLog("Target is construction?: " . $TargetIsConstruction);

            } // End check target
            //check if we can store this item.
            if ($GB_Setting['DoStorage']) {
                $Able2Store = GB_CanWeStore($Unit['0']);
                if ($GB_Setting['StorageLocation'] == 'N') {
                    if ($GB_Setting['DoDebug']) {
                        GB_AddLog("GB no cellar found. Better to switch off storage.");
                    }
                    $Able2Store = 'N';
                } else {
                    if ($GB_Setting['StorageUsed'] >= $GB_Setting['StorageCapacity']) { // storage is full.
                        $Able2Store = 'N';
                        GB_AddLog("GB cellar is full? ");
                    }
                }
                if ($GB_Setting['DoStorage'] && $Able2Store == 'Y' && $GB_ItemAmount > 0) { // check content of storage
                    GB_AddLog("GB entering storage routine for: " . $Unit['0']['realname']);
                    $cellars = unserialize($GB_Setting['StorageContent2']);
                    $AmountInCellar = 0;
                    if (array_key_exists($GB_ItemCode, $cellars)) {
                        $AmountInCellar = $cellars[$GB_ItemCode];
                    }
                    GB_AddLog("GB store " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " into cellar?");
                    if ($GB_Setting['DoStorage1'] && $AmountInCellar < 1 && $GB_ItemCode != "wO" && $GB_ItemCode != "wP") {
                        $Amount2Store = 1;
                        GB_AddLog("GB store " . $Amount2Store . " off " . $Unit['0']['realname'] . " into cellar");
                    } else {
                        GB_AddLog("GB store " . $Unit['0']['realname'] . " already in the cellar");
                    }
                    if ($Amount2Store > 0) {
                        $result = GB_StoreCel($Unit['0'], $Amount2Store);
                        GB_AddLog("GB result: " . $result);
                        //$result = "bla";
                        if ($result == "OK") {
                            GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " added");
                            GB_AddLog($GB_ItemAmount . " " . $Unit['0']['realname'] . " used");
                            $GB_changed = true; // giftbox changed
                            GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Fuel added");
                            $GB_ItemAmount = $GB_ItemAmount - $Amount2Store;
                        } else {
                            GB_AddLog("GB Need to reload");
                            $giftboxs = array();
                            break;
                        }
                    } else {
                        GB_AddLog("GB no need to store this item.");
                    }
                } // end stor

            }
            // check if we can use the item to store into a building
            if ($place_in_build != 0 && $target != 0 && $GB_Setting['DoPlaceBuild'] && $GB_ItemAmount > 0) { //$place_in_max
                GB_AddLog('GB ' . $Target['itemName'] . '(' . $featureCreditsName . ') capacity: ' . $TargetCapacity . ' have: ' . $TotItems);
                $finished = false;
                if ($TotItems >= $TargetCapacity && $vNoLimit<>'on') { // The building is full lets skip this item.
                    GB_AddLog("GB error: This building is full. skipping..");
                    $finished = true;
                }
                while (!$finished) {
                    $result = GB_storeItem($Unit['0'], $Target);
                    if ($result == "OK") {
                        $GB_changed = true;
                        //update the amount.
                        GB_AddLog('GB placed in building ' . $Unit['0']['_name'] . ' total in building: ' . $TotItems);
                        GB_AddLog1($Unit['0']['_name'] . ' placed in building. ');
                        GB_SQL_updAction("_place_in_build", $GB_ItemCode, $place_in_build - $GB_ItemAmount); //$field, $code, $val
                        //update stats.
                        GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Into building");
                        $TotItems++;
                        $GB_ItemAmount--;
                    } else {
                        $finished = true;
                        GB_AddLog("GB Need to reload");
                        $giftboxs = array();
                        break;
                    }
                    if ($TotItems >= $TargetCapacity && $vNoLimit<>'on') {
                        $finished = true;
                    }
                    if ($GB_ItemAmount < 1) {
                        $finished = true;
                    }
                } // not full

            } //end place in build
            // check if we have to place construction in a building
            // construction = # or Y    0 = not construction
            if ($construction == 'Y' && $GB_Setting['DoConstr'] && $GB_ItemAmount > 0) {
                $go = $GB_ItemAmount;
                $used = 0;
                while ($go > 0) {
                    GB_AddLog('GB construction part ' . $Unit['0']['_name'] . ' found  ' . $go . " in Giftbox. ");
                    $GBSQL = "SELECT * FROM BuildingParts WHERE _itemCode = " . Qs($GB_ItemCode) . " AND _action ='construction' AND ((_need - _ObjHave)>0) LIMIT " . $go;
                    #GB_AddLog ("*** SQL Debug: " . __LINE__ . ' ' . $GBSQL );
                    #GB_AddLog ("*** Item log _ Item " .$GB_ItemCode. ' have: '.$GB_ItemAmount );
                    $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
                    if (sqlite_num_rows($query) > 0) {
                        $BuildingParts = sqlite_fetch_all($query);
                        foreach($BuildingParts as $BuildingPart) { // 1 or more targets found
                            $Target = GBSQLGetObjByID($BuildingPart['_ObjId']);
                            GB_AddLog('GB part ' . $BuildingPart['_itemName'] . ' for ' . $BuildingPart['_UnitBuildName'] . " contains " . $BuildingPart['_ObjHave'] . " Adding 1");
                            $result = GB_storeItem($Unit['0'], $Target);
                            if ($result == "OK") {
                                $GB_changed = true;
                                GB_AddLog1($BuildingPart['_itemName'] . ' added in ' . $BuildingPart['_UnitBuildName']);
                                GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Construction");
                                //update the amount have
                                $have = $BuildingPart['_ObjHave'] + 1;
                                $GBSQL = "UPDATE BuildingParts SET _ObjHave=" . Qs($have) . " WHERE _UnitBuildName = " . Qs($BuildingPart['_UnitBuildName']) . " AND _itemCode = " . Qs($GB_ItemCode);
                                $query = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
                                $go--;
                                $used++;
                            } else {
                                GB_AddLog("GB Need to reload");
                                $go = 0;
                                $giftboxs = array();
                                break;
                            }
                        }
                    } else { //skip there are no target buildings
                        GB_AddLog('GB construction part not needed. (' . $GB_ItemCode . ')');
                        $go = 0;
                    }
                } // while $GB_ItemAmount > 1
                $GB_ItemAmount = $GB_ItemAmount - $used;
            } // constructions
            // check if we have Special to handle
            if ($place_in_special != 0 && $target != 0 && $GB_Setting['DoSpecials'] && $GB_ItemAmount > 0) {
                //$Target = GBSQLGetObjByID($target);
                GB_AddLog('GB special: ' . $TargetUnit['0']['_name'] . " for " . $Target['itemName']);
                // for specials there is no max.
                $result = GB_storeItem2($Unit['0'], $Target, $GB_ItemAmount);
                if ($result == "OK") {
                    //GB_SpecialThisUpdate($GB_ItemCode, $GB_ItemAmount);
                    $GB_changed = true;
                    GB_AddLog1($TargetUnit['0']['_name'] . " for " . $Target['itemName']);
                    GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Special");
                    //GB_Stat($GB_ItemCode, $ObjD['realname'],0 ,0, $GB_ItemAmount , 0);
                    $GB_ItemAmount = 0;
                } else {
                    GB_AddLog("GB Need to reload");
                    $giftboxs = array();
                    break;
                }
            }
            // check if we have collection
            if ($collection == 'Y' && $GB_Setting['DoColl'] && $GB_ItemAmount > 0) {
                $Amount_in_Collection = GB_GetColInfo($GB_ItemCode, $GBccount);
                // Check if we have less than 10
                if ($Amount_in_Collection < 10 && $GB_ItemAmount > 0) {
                    if ($Amount_in_Collection + $GB_ItemAmount <= 10) {
                        $Amount_to_add = $GB_ItemAmount;
                    } else {
                        $Amount_to_add = 10 - $Amount_in_Collection;
                    }
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['_name'] . " have " . $Amount_in_Collection . " will add " . $Amount_to_add . "to collection now. ");
                    $result = GB_DoColAdd($Unit['0']['_name'], $GB_ItemCode, $Amount_to_add);
                    if ($result == "OK") {
                        GB_AddLog($Amount_to_add . " " . $Unit['0']['_name'] . " added to collection");
                        GB_AddLog1($Amount_to_add . " " . $Unit['0']['_name'] . " added to collection");
                        $GB_changed = true;
                        GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Added collection");
                        $Amount_in_Collection = $Amount_in_Collection + $Amount_to_add;
                        $GB_ItemAmount = $GB_ItemAmount - $Amount_to_add;
                    } else {
                        GB_AddLog("GB Need to reload");
                        $giftboxs = array();
                        break;
                    }
                } // end < 10
                if ($Amount_in_Collection >= 10 && $GB_ItemAmount > 0) { //we have already 10
                    if ($GB_Setting['DoCollSell']) {
                        GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['_name'] . " have " . $Amount_in_Collection . " will sell now.");
                        $result = GB_DoSellCol($Unit['0'], $GB_ItemAmount);
                        if ($result == "OK") {
                            GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " sold");
                            GB_AddLog1($GB_ItemAmount . " " . $Unit['0']['realname'] . " sold");
                            $GB_changed = true;
                            GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Sold collection");
                        } else {
                            GB_AddLog("GB Need to reload");
                            $giftboxs = array();
                            break;
                        }
                    } else {
                        GB_AddLog("GB" . $GB_ItemAmount . " " . $ObjD['realname'] . " have " . $Amount_in_Collection . " Selling disabeld.");
                    } // end do_sell

                } // end more 10.

            } // end collection
            // Place on farm
            if ($place_on_farm == 'Y' && $GB_Setting['DoPlace'] && $GB_ItemAmount > 0) {
                GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " Place on farm");
                $GB_Where = "Decoration";
                if ($Unit['0']['_type'] == "animal") {
                    $GB_Where = "Animal";
                }
                if ($Unit['0']['_type'] == "tree") {
                    $GB_Where = "Tree";
                }
                //check if there is place on the farm.
                $GB_Free_place = TEmptyXY3($GB_Where, "ALL");
                if ($GB_Free_place < $GB_ItemAmount) {
                    GB_AddLog("****** Error *****");
                    GB_AddLog("GB There is no room on you farm left.");
                    GB_AddLog("GB To place: " . $GB_ItemAmount . " " . $Unit['0']['realname']);
                    GB_AddLog("****** Error *****");
                } else {
                    $result = GB_PlaceM3($Unit['0'], $GB_ItemAmount, $GB_Where);
                    GB_AddLog("GB result: " . $result);
                    if ($result == "OK") {
                        GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " Placed");
                        GB_AddLog1($GB_ItemAmount . " " . $Unit['0']['realname'] . " Placed");
                        $GB_changed = true; // giftbox changed
                        GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Placed");
                        $GB_ItemAmount = 0;
                    } else {
                        GB_AddLog("GB Need to reload");
                        $giftboxs = array();
                        break;
                    }
                }
            } // end place
            if ($Unit['0']['_type'] == 'fuel' && $GB_Setting['DoFuel'] && $GB_ItemAmount > 0) { // selling fuel enabled   >> GB_BuyFuel($ObjD , $GB_amount)
                GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " Fuel found");
                $result = GB_BuyFuel($Unit['0'], $GB_ItemAmount);
                GB_AddLog("GB result: " . $result);
                if ($result == "OK") {
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " added");
                    GB_AddLog($GB_ItemAmount . " " . $Unit['0']['realname'] . " used");
                    $GB_changed = true; // giftbox changed
                    GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GB_ItemAmount, "Fuel added");
                    $GB_ItemAmount = 0;
                } else {
                    GB_AddLog("GB Need to reload");
                    $giftboxs = array();
                    break;
                }
            } // end fuel
            // consumable
            if ($consume == 'Y' && $GB_ItemAmount > 0) {
                $GBAction_Amount = $GB_ItemAmount - $keep;
                if ($GBAction_Amount >= 1) {
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " Consume. Leave in GB: " . $keep);
                    $result = GB_consume($Unit['0'], $GBAction_Amount);
                    GB_AddLog("GB result: " . $result);
                    $need_reload = true;
                    if ($result == "OK") {
                        GB_AddLog("GB " . $GBAction_Amount . " " . $Unit['0']['realname'] . " Consumed");
                        GB_AddLog1($GBAction_Amount . " " . $Unit['0']['realname'] . " Consumed");
                        $GB_changed = true; // giftbox changed
                        GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Consume");
                        $GB_ItemAmount = 0;
                    } else {
                        GB_AddLog("GB Need to reload");
                        $giftboxs = array();
                        break;
                    }
                } else {
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " keep in GB: " . $keep);
                }
            } // end consumable
            // DoVehicle
            if ($GB_Setting['DoVehicle'] && $GB_ItemCode == "dS" && $GB_ItemAmount > 0) {
                $GBAction_Amount = $GB_ItemAmount - $keep;
                $vehicle = GB_garage('hook');
                $AmountOfRuns = $vehicle['0']['vehicle'];
                GB_AddLog("GB have " . $GB_ItemAmount . " vehicleparts. have " . $vehicle['0']['vehicle'] . " vehicle that need parts");
                while ($AmountOfRuns >= 1 && $GBAction_Amount > 1) {
                    $needpart = $vehicle[$AmountOfRuns]['need'];
                    GB_AddLog("GB vehicle " . $vehicle[$AmountOfRuns]['itemCode'] . " need " . $needpart . " parts");
                    while ($needpart >= 1 && $GBAction_Amount > 1) {
                        $result = GB_DoGarage($vehicle[$AmountOfRuns], $vehicle['0']['id']);
                        //GB_AddLog("GB result: ". $result );
                        //$need_reload = true;
                        if ($result == "OK") {
                            $vehicle[$AmountOfRuns]['numParts'] = $vehicle[$AmountOfRuns]['numParts'] + 1;
                            $needpart--;
                            GB_AddLog("GB 1 " . $Unit['0']['realname'] . " added, need " . $needpart . ' more');
                            GB_AddLog1($GBAction_Amount . " " . $Unit['0']['realname'] . " added");
                            $GB_changed = true; // giftbox changed
                            GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Added");
                            $GBAction_Amount--;
                            $GB_ItemAmount--;
                        } else {
                            GB_AddLog("GB Need to reload");
                            $giftboxs = array();
                            break;
                        }
                    }
                    $AmountOfRuns--;
                } //else {GB_AddLog("GB " . $GB_ItemAmount ." ". $Unit['0']['realname'] . " keep in GB: " . $keep);}

            } // end DoVehicle
            // check if we can open this.
            //$GB_OpenArray = array('MG', 'Sa', 'Sb', 'Sc', 'Sd', 'Gf', 'Gg', 'Gh', 'Gz', 'JU', 'dR', 'dP', 'dQ');
            $GB_OpenArray = unserialize($GB_Setting['OpenItems']);
            if (in_array($GB_ItemCode, $GB_OpenArray)) {
                $GB_OpenThis = 'Y';
            } else {
                $GB_OpenThis = 'N';
            }
            if ($GB_Setting['DoMystery'] && $GB_ItemAmount > 0 && $GB_OpenThis == 'Y') {
                $GBAction_Amount = $GB_ItemAmount - $keep;
                if ($GBAction_Amount >= 1) {
                    //$GBAction_Amount = 1;
                    GB_AddLog("GB open " . $GB_ItemAmount . " " . $Unit['0']['realname'] . "  ");
                    $result = GB_OpenGift($Unit['0'], $GBAction_Amount);
                    GB_AddLog("GB result: " . $result);
                    $need_reload = true;
                    if ($result == "OK") {
                        GB_AddLog("GB opened " . $GBAction_Amount . " " . $Unit['0']['realname'] . " done");
                        GB_AddLog1($GBAction_Amount . " " . $Unit['0']['realname'] . " opened");
                        $GB_changed = true; // giftbox changed
                        GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Opened");
                        $GB_ItemAmount = 0;
                    } else {
                        GB_AddLog("GB Need to reload");
                        $giftboxs = array();
                        break;
                    }
                } else {
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " keep in GB: " . $keep);
                }
            } // end open
            if ($selling == 'Y' && $GB_Setting['DoSelling'] && $GB_ItemAmount > 0) {
                $GBAction_Amount = $GB_ItemAmount - $keep;
                if ($GBAction_Amount >= 1) {
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " will be sold. Leave in GB: " . $keep);
                    $result = GB_DoSellCol($Unit['0'], $GBAction_Amount);
                    GB_AddLog("GB result: " . $result);
                    $need_reload = true;
                    if ($result == "OK") {
                        GB_AddLog("GB " . $GBAction_Amount . " " . $Unit['0']['realname'] . " Sold");
                        GB_AddLog1($GBAction_Amount . " " . $Unit['0']['realname'] . " Sold");
                        $GB_changed = true; // giftbox changed
                        GB_Stat3($GB_ItemCode, $Unit['0']['_name'], $GBAction_Amount, "Sold");
                        $GB_ItemAmount = 0;
                    } else {
                        GB_AddLog("GB Need to reload");
                        $giftboxs = array();
                        break;
                    }
                } else {
                    GB_AddLog("GB " . $GB_ItemAmount . " " . $Unit['0']['realname'] . " keep in GB: " . $keep);
                }
            } // end Do_SellList

        } //end foreach giftbox
        // after giftbox, see the collection trade in.
        if ($GB_Setting['DoCollTrade']) { // now look to complete collections.
            GB_AddLog("Collection trade in set to: " . $GB_Setting['DoCollKeep']);
            $GB_CollCompete_res = GB_CollCompete(); // get the information
            while (list($GB_CollCode, $amount) = each($GB_CollCompete_res)) {
                GB_AddLog("Collection info: [" . $GB_CollCode . "] has" . $amount . " completed");
                $GB_Tradein_amount = $amount - $GB_Setting['DoCollKeep'];
                if ($GB_Tradein_amount > 0) {
                    $GB_TradeIn_res = GB_TradeIn($GB_CollCode, $GB_Tradein_amount);
                    GB_AddLog("Collection trade in: [" . $GB_CollCode . "] " . $GB_Tradein_amount . " time(s)");
                    GB_AddLog1("Collection trade in: [" . $GB_CollCode . "] " . $GB_Tradein_amount . " time(s)");
                    GB_Stat3($GB_CollCode, "Collection", $GB_Tradein_amount, "Trade in");
                    //GB_Stat($GB_CollCode, "Collection", 0, 0, 0, $GB_Tradein_amount );
                    $GB_changed = true;
                }
            }
        } // end complete collection

    } else {
        GB_AddLog("Skipping giftbox...");
    }
    if ($GB_changed) {
        $res = GB_renew_giftbox_SQL(); // update giftbox. so it shows correctly in the screen.

    } else {
        $T2 = time();
        $T2-= $T;
        GB_AddLog("Giftbox done. took " . $T2 . " Seconds");
    }
} // end function

?>
