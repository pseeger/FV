<?php
//============================================================================
//
//  Get all obj.
//  Put it into DB
//
//
//============================================================================
function GBCreateMap() {
    $logtext = "";
    global $GBDBmain;
    global $GBDBuser;
    $ObjTemps = array();
    // Get all objects
    //$GBSQL = "SELECT _obj,_set,_val from objects WHERE _set = 'itemName' OR _set = 'position' OR _set = 'className' OR _set = 'direction' OR _set = 'state'";
    $GBSQL = "SELECT _obj,_set,_val from objects";
    $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    $Objects = sqlite_fetch_all($result);
    foreach($Objects as $Object) {
        $ObjTemps[$Object['_obj']][$Object['_set']] = $Object['_val'];
        if ($Object['_set'] == 'position') {
            $ObjTemps[$Object['_obj']][$Object['_set']] = unserialize($Object['_val']);
        }
    }
    //GB_AddLog("GB read objects .. ");
    $logtext.= " Amount of Object lines found: " . "\r\n";
    $logtext.= count($Objects) . "\r\n";
    // get all units
    $Units = array();
    $GBSQL = "SELECT _name,_code,_sizeX,_sizeY,_className from units WHERE _code != 'NULL'";
    //$GBSQL = "SELECT _name,_code,_sizeX,_sizeY,_className FROM units WHERE _code != 'NULL' AND _sizeX IS NOT NULL AND _sizeY IS NOT NULL";
    $result = sqlite_query($GBDBmain, $GBSQL) or GBSQLError($GBDBmain, $GBSQL);
    $UnitsSQL = sqlite_fetch_all($result);
    foreach($UnitsSQL as $Unit) {
        $index = $Unit['_name']; // . "-" . ucwords($Unit['_className']);
        $Units[$index]['itemName'] = $Unit['_name'];
        $Units[$index]['code'] = $Unit['_code'];
        $Units[$index]['sizeX'] = $Unit['_sizeX'];
        $Units[$index]['sizeY'] = $Unit['_sizeY'];
        $logtext.= "Unit found: " . $index . " " . $Unit['_name'] . " - " . $Unit['_code'] . " - " . $Unit['_sizeX'] . " - " . $Unit['_sizeY'] . "\r\n";
    }
    if (count($Units) < 1000) { // some thing when wrong, units not good
        GB_AddLog("GB fail reading units");
        return 'Fail';
    }
    //GB_AddLog("GB read units .. ");
    $logtext.= " Amount of Units found: " . "\r\n";
    $logtext.= count($UnitsSQL) . "\r\n";
    $i = 0;
    foreach($ObjTemps as $Object) {
        //   echo "Next: <br>";
        $index = $Object['itemName']; //. "-" . $Object['className']  ;
        $logtext.= "$i className: " . $Object['className'] . "\r\n";
        $logtext.= "$i Object itemName: " . $Object['itemName'] . "\r\n";
        //$logtext .="$i Object sizeX: " .  $Units[$index]['sizeX']  .  "\r\n";
        //$logtext .="$i Object sizeY: " .  $Units[$index]['sizeY']  .  "\r\n";
        if ($Object['className'] == "Plot") {
            $sizeX = "4";
            $sizeY = "4";
            $code = "Plot";
        } else {
            if (isset($Units[$index]['sizeX'])) {
                $sizeX = $Units[$index]['sizeX'];
            } else {
                $sizeX = "1";
            }
            if ($sizeX == '0') {
                $sizeX = "1";
            }
            if (isset($Units[$index]['sizeY'])) {
                $sizeY = $Units[$index]['sizeY'];
            } else {
                $sizeY = "1";
            }
            if ($sizeY == '0') {
                $sizeY = "1";
            }
            if (isset($Units[$index]['code'])) {
                $code = $Units[$index]['code'];
            } else {
                $code = "NOT";
            }
        }
        if (isset($Object['direction'])) {
            $direction = $Object['direction'];
        } else {
            $direction = "";
        }
        $itemName = $Object['itemName'] . "_" . $Object['className'];
        $className = $Object['className'];
        $state = $Object['state'];
        $posX = $Object['position']['x'];
        $posY = $Object['position']['y'];
        $Map_all_items[$i] = array('itemName' => $itemName, 'classname' => $className, 'state' => $state, 'posX' => $posX, 'posY' => $posY, 'direction' => $direction, 'sizeX' => $sizeX, 'sizeY' => $sizeY, 'code' => $code, 'end' => " ");
        $logtext.= "$i  $itemName  $className x:$posX  y:$posY dir:$direction size x:$sizeX y:$sizeY  code:$code state: $state \r\n";
        $i++;
        //print_r($Object);

    }
    $logtext.= " Amount of objects prepared: " . $i . "\r\n";
    // GB_AddLog("GB prep objects .. ");
    // now map the objects to positions
    sqlite_query($GBDBuser, 'BEGIN;');
    // empty the all locations
    $GBSQL = "DELETE FROM locations";
    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
    // get the farm size
    @list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', @file_get_contents(F('playerinfo.txt')));
    if (($FarmSizeX == '') || ($FarmSizeY == '')) {
        $GB_place_items = "No";
        return;
    } else {
        $GB_place_items = "OK";
    }
    // fill the location with empty
    $X = 0;
    while ($X < $FarmSizeX) {
        $Y = 0;
        while ($Y < $FarmSizeY) {
            $GBSQL = "INSERT INTO locations(_X,_Y,_what) VALUES('" . $X . "','" . $Y . "','E')";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            $Y++;
        }
        $X++;
    }
    sqlite_query($GBDBuser, 'END;');
    //GB_AddLog("GB objects saved.. ");
    sqlite_query($GBDBuser, 'BEGIN;');
    $GBSQL = "";
    foreach($Map_all_items as $Map_pos) {
        //$logtext .="Next item".  "\r\n";
        //$logtext .="name: " . $Map_pos['itemName'].  "\r\n";
        //$logtext .="location: " . $Map_pos['posX'] . " - " . $Map_pos['posY'].  "\r\n";
        if ($Map_pos['sizeX'] == "1" && $Map_pos['sizeY'] == "1") { // size 1x1
            $GBSQL = "UPDATE locations SET _what = '" . $Map_pos['classname'] . "' WHERE _X = '" . $Map_pos['posX'] . "' AND _Y = '" . $Map_pos['posY'] . "' ;";
            sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
        } else { // size not 1x1
            if (!array_key_exists('state', $Map_pos)) {
                $Map_pos['state'] = "none";
            }
            if ($Map_pos['state'] == "vertical" || $Map_pos['state'] == "built_rotatable") { // object = turned.
                $TotY = $Map_pos['sizeX'];
                $TotX = $Map_pos['sizeY'];
                //$logtext .="found vertical ".$Map_pos['itemName']. " X:" . $TotX . " Y:" . $TotY .  "\r\n";

            } else { // object is normal.
                $TotX = $Map_pos['sizeX'];
                $TotY = $Map_pos['sizeY'];
                //$logtext .="found normal ".$Map_pos['itemName']. " X:" . $TotX . " Y:" . $TotY .  "\r\n";

            }
            // now TotX & Y is corrected.
            while ($TotX > 0) {
                //echo "*";
                $TotYtmp = $TotY;
                while ($TotYtmp > 0) {
                    $MapX = $Map_pos['posX'] + $TotX - 1;
                    $MapY = $Map_pos['posY'] + $TotYtmp - 1;
                    $logtext.= "   pos:" . $MapX . "-" . $MapY . "\r\n";
                    //$GBSQL ="UPDATE locations SET _what = '".$Map_pos['itemName']."' WHERE _X = '".$MapX."' AND _Y = '".$MapY."' ;";
                    $GBSQL = "UPDATE locations SET _what = '" . $Map_pos['classname'] . "' WHERE _X = '" . $MapX . "' AND _Y = '" . $MapY . "' ;";
                    //$logtext .="SQL: " . $GBSQL.  "\r\n";
                    sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
                    $TotYtmp--;
                } //end while Y
                $TotX--;
            } // end while X

        } // end else

    }
    //GB_AddLog("GB empty locations found.. ");
    //sqlite_query($GBDBuser, $GBSQL) or GB_AddLog ("*** SQL Error *** " . $GBSQL );
    GB_AddLog("GB empty locations saved.. ");
    sqlite_query($GBDBuser, 'END;');
    //GB_AddLog("GB empty locations commit.. ");
    echo "Done <br>";
    //print_r($Map_all_items);
    echo " <br>";
    // dump the logfile.
    $f = fopen('GB_XY_mapLOG.txt', "w+");
    fputs($f, $logtext, strlen($logtext));
    fclose($f);
}
//============================================================================
//   function TEmptyXY
//  returns the total empty slots in a block
// $loc = the location Animal, Tree or Decoration.
// $amount = ALL or ONE
//============================================================================
function TEmptyXY3($loc, $amount) {
    global $GB_Setting;
    global $GBDBmain;
    global $GBDBuser;
    $cont = true;
    $counter = 0;
    if (!in_array($loc, array("Animal", "Tree", "Decoration"))) {
        $cont = false;
    }
    if (!in_array($amount, array("ALL", "ONE"))) {
        $cont = false;
    }
    $minX = $GB_Setting[$loc . 'X1'];
    $minY = $GB_Setting[$loc . 'Y1'];
    $maxX = $GB_Setting[$loc . 'X2'];
    $maxY = $GB_Setting[$loc . 'Y2'];
    $locations = "ERROR";
    if ($cont) {
        @list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', @file_get_contents(F('playerinfo.txt')));
        if (($FarmSizeX == '') || ($FarmSizeY == '')) {
            $GB_place_items = "No";
            return;
        } else {
            $GB_place_items = "OK";
        }
        if ($amount == "ONE") {
            $GBSQL = "SELECT * FROM locations WHERE _X >= '" . $minX . "' AND _X <= '" . $maxX . "' AND _Y >= '" . $minY . "' AND _Y <= '" . $maxY . "' AND _what = 'E' LIMIT 1";
            $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            if (sqlite_num_rows($result) > 0) {
                $temp = sqlite_fetch_all($result);
                $locations = array();
                $locations['x'] = $temp['0']['_X'];
                $locations['y'] = $temp['0']['_Y'];
                // reserv the spot in the DB
                $GBSQL = "UPDATE locations SET _what = 'TEMP Giftbox' WHERE _X = '" . $locations['x'] . "' AND _Y = '" . $locations['y'] . "' ";
                sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
                //$debug = "1";
                //if($debug) AddLog2("GB DEBUG: X: " . $temp['0']['_X'] . " Y: " . $temp['0']['_Y'] . " ");
                // if($debug) AddLog2("GB DEBUG: Y: " . $temp['0']['_Y'] . " ");

            } else {
                $locations = "fail";
            }
        }
        if ($amount == "ALL") {
            $GBSQL = "SELECT count(*) FROM locations WHERE _X >= '" . $minX . "' AND _X <= '" . $maxX . "' AND _Y >= '" . $minY . "' AND _Y <= '" . $maxY . "' AND _what = 'E' ";
            $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
            if (sqlite_num_rows($result) > 0) {
                $location = sqlite_fetch_all($result);
                $locations = $location['0']['count(*)'];
            } else {
                $locations = "fail";
            }
        }
        DebugLog(" << TEmptyXY");
    } //else {return $locations;} // paramter wrong
    return $locations;
} // end function
//============================================================================
//   function TEmptyXY
//  returns the total empty slots in a block
// $loc = the location Animal, Tree or Decoration.
// $amount = ALL or ONE
//============================================================================
function TEmptyXYSQL($loc, $amount) {
    global $GB_Setting;
    global $GBDBmain;
    global $GBDBuser;
    $cont = true;
    $counter = 0;
    if (!in_array($loc, array("Animal", "Tree", "Decoration"))) {
        $cont = false;
    }
    if (!in_array($amount, array("ALL", "ONE"))) {
        $cont = false;
    }
    //$GB_Setting['userid']
    $minX = $GB_Setting[$loc . 'X1'];
    $minY = $GB_Setting[$loc . 'Y1'];
    $maxX = $GB_Setting[$loc . 'X2'];
    $maxY = $GB_Setting[$loc . 'Y2'];
    if ($cont) {
        @list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', @file_get_contents(F('playerinfo.txt')));
        if (($FarmSizeX == '') || ($FarmSizeY == '')) {
            $GB_place_items = "No";
            return;
        } else {
            $GB_place_items = "OK";
        }
        if (file_exists("plugins/GiftBox/" . $GB_Setting['userid'] . "_" . GBox_XY_map)) {
            $MapXY = load_array(GBox_XY_map);
        } else {
            AddLog2("GB_XY_map.txt not found");
            return "Not indexed yet.";
        }
        $Map_pos_x = $minX;
        while ($Map_pos_x < $maxX) {
            $Map_pos_y = $minY;
            while ($Map_pos_y < $maxY) {
                if (!array_key_exists($Map_pos_x . "-" . $Map_pos_y, $MapXY)) {
                    // empty position found
                    $EmptyXY['x'] = $Map_pos_x;
                    $EmptyXY['y'] = $Map_pos_y;
                    if ($amount == "ONE") {
                        $MapXY[$Map_pos_x . "-" . $Map_pos_y] = "temp_Giftbox";
                        save_array($MapXY, GBox_XY_map);
                        return $EmptyXY;
                    } else {
                        $counter++;
                    }
                }
                $Map_pos_y++;
            }
            $Map_pos_x++;
        }
        if ($amount == "ONE") {
            return "fail";
        }
        DebugLog(" << TEmptyXY");
        return $counter;
    } else {
        return "fail";
    } // paramter wrong

} // end function

?>