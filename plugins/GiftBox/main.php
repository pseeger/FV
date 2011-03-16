<?php
define ( 'GiftBox_version', '3.4.5' );
define ( 'GiftBox_date', '24 Feb 2011' );
define ( 'GiftBox_URL', '/plugins/GiftBox/main.php');
define ( 'GiftBox_HelpURL', '/plugins/GiftBox/main.php?url=help#');
define ( 'GiftBox_TextURL', '/plugins/GiftBox/main.php?url=');
define ( 'GiftBox_Path', '/plugins/GiftBox/');
// file definitions
define ( 'GBox_SettingList', 'GB_settings.txt' );
define ( 'GBox_SellingList_building', 'GB_selling_building.txt' );
define ( 'GBox_SellingList_animal', 'GB_selling_animal.txt' );
define ( 'GBox_SellingList_decoration', 'GB_selling_decoration.txt' );
define ( 'GBox_SellingList_tree', 'GB_selling_tree.txt' );
define ( 'GBox_SellingList_consume', 'GB_selling_consume.txt' );
define ( 'GBox_SellingList', 'GB_selling.txt' );
define ( 'GBox_Statistics', 'GB_Statistics.txt' );
define ( 'GBox_PlaceList_animal', 'GB_Place_animal.txt' );
define ( 'GBox_PlaceList', 'GB_Place.txt' );
define ( 'GBox_XY_objects', 'GB_XY_objects.txt' );
define ( 'GBox_XY_map', 'GB_XY_map.txt' );
define ( 'GBox_storage', 'GB_StorageInfo.txt' );
/******************GiftBox manager by Christiaan****************************/
define ( 'GBox_DB_main', 'GB_DB_main.sqlite');
define ( 'GBox_DB_user', 'GB_DB.sqlite');
/******************GiftBox manager by Christiaan****************************/
include "helpers/GiftBox_Map.php";
include "helpers/GiftBox_AMF.php";
include "helpers/GiftBox_Hook.php";
include "helpers/GiftBox_Screen.php";
include "helpers/GiftBox_Image.php";
include "helpers/GiftBox_sql.php";
/******************GiftBox manager by Christiaan****************************/
function GiftBox_init()
{
    global $hooks;
    global $this_plugin;
    global $is_debug;
    //global $vDataDB;

    $hooks['after_planting'] = 'Giftbox';
//    $hooks['after_work'] = 'GB_renew_giftbox';  //GB_renew_giftbox_SQL
    $hooks['after_work'] = 'GB_renew_giftbox_SQL';  //
//    $hooks['after_work'] = 'GB_renew_giftbox_SQL';
    echo "Loading GiftBox Manager V". GiftBox_version . " Plugin by Christiaan\r\n";
    global $GBox_Settings;
//    GB_load_Settings();
//    $GBox_Settings = load_array ( GBox_SettingList );
}
/******************GiftBox manager by Christiaan****************************/



























//------------------------------------------------------------------------------
// write to logfile for debugging.
//------------------------------------------------------------------------------
function GB_AddLog2($str)
  {
  global $is_debug;
 if (@count(file(F("GiftBox2.txt"))) >= 200000) // normal 200 debugging set higher.
  {
    $arr = file(F("GiftBox2.txt"));
    AddLog2 ("GiftBox log file is ..." . sizeof($arr));
    $lineToDelete = sizeof($arr)-25;        // leave last 25 lines in logfile
    while ($lineToDelete >= 1):
        unset($arr["$lineToDelete"]);       //remove the line
        $lineToDelete--;
    endwhile;
    if (!$fp = fopen(F("GiftBox2.txt"), 'w+'))  // open the file for reading
       {
       AddLog2 ("Cannot GiftBox log file...");
       exit;       // exit the function can not open giftbox logfile
       }
    if($fp)        // if $fp is valid
      {
       foreach($arr as $line) { fwrite($fp,$line); } // write the array to the file
       fclose($fp);         // close the file
      }                     // end writing to file
  } // file is correct size, contineu
  $f = fopen(F("GiftBox2.txt"), "a+");
  if ($f)
   {
   fputs($f, @date("Y-M-d H:i:s")." ".$str."\r\n"); // adding the log line
   fclose($f);
   }
  //if ($is_debug) echo "Log2: " . $str."\r\n";
  //AddLog2 ($str);
  }
//------------------------------------------------------------------------------
// find if collection is complete
//------------------------------------------------------------------------------
function GB_CollAmount()
{
    $GB_CollectionList = GB_GetCollectionList()  ;
    if (!$GB_CollectionList) { return false; }
    $GB_CollCompl = 0;
    //$GB_units = array();
    //$GB_units = GB_GetUnitList();
    //if (!$GB_units) { return false; }
    $GBccount = array();
    $GBccount = GB_LoadCcount();
    if (!$GBccount) { return false; }

    foreach($GB_CollectionList as $value)
       {
       // walk all collections
       $GB_amount_Coll = count($value['collectable']);
       $i=0;
       $GB_ThisCollCompl = 0;
       $GB_ThisCollVal = array();

       while($i < $GB_amount_Coll)
         {
            // each collection
            //$ObjD = GB_GetUnitInfo($value['collectable'][$i], "code", $GB_units);
            $Amount_in_Collection = GB_GetColInfo($value['collectable'][$i], $GBccount);
            $GB_ThisCollVal[] =$Amount_in_Collection;
            $i++;
          }
         $GB_ThisCollCompl = min($GB_ThisCollVal);
         $GB_CollCompl = $GB_CollCompl + $GB_ThisCollCompl;
       }
  //if($GB_CollCompl > 0)  AddLog2("## ". $GB_CollCompl . " Collections Complete");
  return $GB_CollCompl; //return total completed
}
//------------------------------------------------------------------------------
// write to logfile for normal log.
//------------------------------------------------------------------------------
 function GB_AddLog1($str)
{
  global $is_debug;
  global $GB_Setting;
  if($GB_Setting['DoDebug'])GB_AddLog2($str);  // add also to debug log
  AddLog($str);                // add also to log 2 screen.
  if (@count(file(F("GiftBox.txt"))) >= 2000)
  {
    $arr = file(F("GiftBox.txt"));
    AddLog2 ("GiftBox log file is ..." . sizeof($arr));
    $lineToDelete = sizeof($arr)-25;        // leave last 25 lines in logfile
    while ($lineToDelete >= 1):
        unset($arr["$lineToDelete"]);   //remove the line
        $lineToDelete--;
    endwhile;
    if (!$fp = fopen(F("GiftBox.txt"), 'w+'))   // open the file for reading
       {  AddLog2 ("Cannot GiftBox log file...");
          exit;      // exit the function
       }
    if($fp)    // if $fp is valid
      { foreach($arr as $line) { fwrite($fp,$line); }         // write the array to the file
        fclose($fp);         // close the file
      }
  } // file is correct size, contineu
  $f = fopen(F("GiftBox.txt"), "a+");
  if ($f)
   { fputs($f, @date("Y-M-d H:i:s")." ".$str."\r\n");
     fclose($f);
   }
  if ($is_debug) echo "Log2: " . $str."\r\n";
}



 function GB_AddLog($str)
{
  global $is_debug;
  global $GB_Setting;
//  if($GB_Setting['DoDebug'])GB_AddLog2($str);  // add also to debug log
  AddLog2($str);                // add also to log 2 screen.
  if (@count(file(F("GiftBox.txt"))) >= 2000)
  {
    $arr = file(F("GiftBox.txt"));
    AddLog2 ("GiftBox log file is ..." . sizeof($arr));
    $lineToDelete = sizeof($arr)-25;        // leave last 25 lines in logfile
    while ($lineToDelete >= 1):
        unset($arr["$lineToDelete"]);   //remove the line
        $lineToDelete--;
    endwhile;
    if (!$fp = fopen(F("GiftBox.txt"), 'w+'))   // open the file for reading
       {  AddLog2 ("Cannot GiftBox log file...");
          exit;      // exit the function
       }
    if($fp)    // if $fp is valid
      { foreach($arr as $line) { fwrite($fp,$line); }         // write the array to the file
        fclose($fp);         // close the file
      }
  } // file is correct size, contineu
  $f = fopen(F("GiftBox.txt"), "a+");
  if ($f)
   { fputs($f, @date("Y-M-d H:i:s")." ".$str."\r\n");
     fclose($f);
   }
  if ($is_debug) echo "Log2: " . $str."\r\n";
}

//------------------------------------------------------------------------------
// GetCollectionList gets a list of objects in the Storage
//------------------------------------------------------------------------------
function GB_GetCollectionList()
{
  DebugLog(" >> GetCollectionList");
  if (!file_exists('collectable_info.txt')) { return false; }
  return unserialize(file_get_contents('collectable_info.txt')); //return all collections info
  DebugLog(" << GetCollectionList");
}
//------------------------------------------------------------------------------
// find which collection is complete
//------------------------------------------------------------------------------
function GB_CollCompete()
{
    $GB_CollectionList = GB_GetCollectionList()  ;
    if (!$GB_CollectionList) { GB_AddLog("collectable_info.txt not found.. "); return false; }
    $GB_CollCompl = 0;
    $GBccount = array();
    $GBccount = GB_LoadCcount();
    if (!$GBccount) { GB_AddLog("ccount.txt not found.. "); return false; }
    $res = array();

    foreach($GB_CollectionList as $value)
       {
       // walk all collections
       $GB_amount_Coll = count($value['collectable']);
       $i=0;
       $GB_ThisCollCompl = 0;
       $GB_ThisCollVal = array();
       while($i < $GB_amount_Coll)
         {
            // each collection
            //$ObjD = GB_GetUnitInfo($value['collectable'][$i], "code", $GB_units);
            $Amount_in_Collection = GB_GetColInfo($value['collectable'][$i], $GBccount);
            $GB_ThisCollVal[] =$Amount_in_Collection;
            $i++;
          }
       $GB_ThisCollCompl = min($GB_ThisCollVal);
       $res[$value['code']] = $GB_ThisCollCompl;
       }
  return $res; //return total completed array can be empty.
}
//------------------------------------------------------------------------------
// GB_LoadCcount  load the ccount file to get the amount of collectable
//------------------------------------------------------------------------------
function GB_LoadCcount()
{
  global $this_plugin;
  if (!file_exists(F('ccount.txt'))) { return false; }
   return unserialize(file_get_contents(F('ccount.txt')));
}
//------------------------------------------------------------------------------
// GB_GetColInfo    Get count of collectables in collection.
//------------------------------------------------------------------------------
function GB_GetColInfo($needle, $haystack)
{
  global $this_plugin;
    $found = "0";
    if (array_key_exists($needle, $haystack)) {
            $found = $haystack[$needle]; // return the amount.
    }
    return $found;
}


//========================================== FONTS ETC
function GBHead($text)
{
$textformated = '<span style="color:#240B3B; background-color:#A9D0F5; font-weight:bold; font-size:20px ">'. $text ."</span>";
return $textformated;
}

function GB_UrlText($url, $Text)
{
  $GB_OnMouse = '<b><a href="'. GiftBox_TextURL .''.$url.'" title="Jump to tab"><font color="CC0000">'.$Text.'</font></a></b>';
  return $GB_OnMouse;
}


function GB_HelpText($url, $Help)
{
  $GB_OnMouse = '<i><b><a href="'. GiftBox_HelpURL .''.$url.'" title="Jump to help"><font color="CC0000">'.$Help.'</font></a></b></i>';
  return $GB_OnMouse;
}
function Old___GB_HelpText($Text, $Help)
{
  $GB_OnMouse = '<i><b><span title="header=[Help] body=[' . $Text . ']" style="color:#08088A; background-color:#BDBDBD; font-size:9px ">'.$Help.'</span></b></i>';
  return $GB_OnMouse;
}

function GB_HelpTextbak($Text, $Help)
{
      $GB_OnMouse = '<i><b onmouseover="Tip(\'' . $Text . '\')" onmouseout="UnTip()" ><span style="color:#08088A; background-color:#BDBDBD; font-size:9px "> '.$Help.' </span></b></i>';
  return $GB_OnMouse;
}

function GB_loadtime($GB_starttime, $text)
{
    global $GB_starttime;
    $GB_endtime = microtime();
$GB_endarray = explode(" ", $GB_endtime);
$GB_endtime = $GB_endarray[1] + $GB_endarray[0];
$GB_totaltime = $GB_endtime - $GB_starttime;
$GB_totaltime = round($GB_totaltime,5);
echo "$text - Load time $GB_totaltime seconds.";
}

function GB_loadtime2($GB_starttime, $text)
{
    global $GB_starttime;
    $GB_endtime = microtime();
$GB_endarray = explode(" ", $GB_endtime);
$GB_endtime = $GB_endarray[1] + $GB_endarray[0];
$GB_totaltime = $GB_endtime - $GB_starttime;
$GB_totaltime = round($GB_totaltime,5);
return "$text - Load time $GB_totaltime seconds.<br>";
}

// building the tab tables.
function GB_SmartFilter($i)
{
  global $GBox_Settings;
  $echo  = '<form name="Filter'.$i.'" action="'. GiftBox_URL .'">
            Enable smart filtering <input name="Filter" type="checkbox" '.($GBox_Settings['Filter']?'checked':'').' value="1" onClick="SubmitFilter'.$i.'()">
            </form>';
return $echo;
}

function GB_TabTable1($FromName, $GB_url = 'giftbox')
{
     $echo  = '<table id="'.$FromName.'" class="mytable" cellspacing="0" cellpadding="0">';
     $echo .= '<form name="'.$FromName.'" action="'. GiftBox_URL .'" method="get">';
     $echo .= '<input type="submit" value="Save changes" />';
     //$echo .= '<input type="button" name="Check_All" value="Check All Sell" onClick="CheckAll(document.'.$FromName.'.S)">';
     //$echo .= '<input type="button" name="Un_CheckAll" value="Uncheck All Sell" onClick="UnCheckAll(document.'.$FromName.'.S)">';
     //$echo .= '<input type="button" name="Check_All" value="Check All Place" onClick="CheckAll(document.'.$FromName.'.P)">';
     //$echo .= '<input type="button" name="Un_CheckAll" value="Uncheck All Place" onClick="UnCheckAll(document.'.$FromName.'.P)">';
     $echo .= '<input type="hidden" name="update2" value="'.$FromName.'" /> ';
     //$echo .= '<br> ';

     //$echo .= '<input type="button" name="Check_All" value="Check All Place" onclick="setCheckedValue(document.forms[\''.$FromName.'\'].elements[\'Bu\'], \'P\')">';
     $echo .= '<input type="button" name="Check_All" value="Check All Place" onclick="setCheckedValue(document.forms[\''.$FromName.'\'], \'P\')">';
     $echo .= '<input type="button" name="Check_All" value="Check All Sell"  onclick="setCheckedValue(document.forms[\''.$FromName.'\'], \'S\')">';
     $echo .= '<input type="button" name="Check_All" value="Uncheck All"     onclick="setCheckedValue(document.forms[\''.$FromName.'\'], \'0\')">';

     //$echo .= '<input type="hidden" name="S" value="'.$FromName.'" /> ';
     //$echo .= '<input type="hidden" name="P" value="'.$FromName.'" /> ';
     $echo .= '<input type="hidden" name="url" value="'.$GB_url.'" /> ';
     $echo .= "<tr><th>Image</th><th>Name</th><th>Setting</th><th>Code</th></tr>";
return $echo;
}

function GB_TabTable2($FromName)
{
  $echo  = '</form></table><br>';
  $echo .= '<script language="javascript" type="text/javascript">
    var props = {
        col_0: "none",
        on_keyup: true,
        on_keyup_delay: 1200
    }
    setFilterGrid("'.$FromName.'",props);
          </script> ';
return $echo;
}

function GB_TabScript1($FromName)
{
  $echo = '<script language="javascript" type="text/javascript">
    var props'.$FromName.' = {
        on_keyup: true,
        on_keyup_delay: 1200
    }
    setFilterGrid("'.$FromName.'",props'.$FromName.');
          </script> ';
return $echo;
}

// format the input butons.
// $what = checkbox of button
// function GB_input2($code, $type, $unit, $action, $what)
function GB_input2($unit, $action, $what)
{
 global $GBDBmain;
 global $GBDBuser;
 global $GBGetTemp;

  $sell = "NO";
  $place = "NO";
  //print_r($unit);
  $sizeX = "0";
  $sizeY = "0";
  if(array_key_exists('_code',$unit))
    {
      $code = $unit['_code'];
      $sizeX = $unit['_sizeX'];
      $sizeY = $unit['_sizeY'];
      $type = $unit['_type'];
      //$ = $unit['_'];
    }
    else
    {
      $code = $unit['0']['_code'];
      $sizeX = $unit['0']['_sizeX'];
      $sizeY = $unit['0']['_sizeY'];
      $type = $unit['0']['_type'];
    }
  // check for double code.
  $GBGetTempCODE = strtoupper($code);
  if(array_key_exists($GBGetTempCODE,$GBGetTemp))
    {
     $GBGetTemp[$GBGetTempCODE] = $GBGetTemp[$GBGetTempCODE] + 1;
     $GBC = $GBGetTemp[$GBGetTempCODE];
    }
    else
    {
     $GBGetTemp[$GBGetTempCODE] = 0;
     $GBC = '';
    }

  $ActionN = "CHECKED";
  $ActionS = "";
  $ActionP = "";
  $ActionC = "";
  $keep = 0;
  $consume = 'N';
  //echo 'function code '. $code .'<br>';
  //set variable good.
  if($sizeX == ""){$sizeX = "0";}
  if($sizeY == ""){$sizeY = "0";}
  // can we place this item?
  $extraText = "";
  if($type == 'tree' ){$sizeX = "1";  $sizeY = "1";}
  if($sizeX == "1" && $sizeY == "1" ){$placeable = "Y";}else{$placeable = "N";}
  if (!empty($action))
   {
     if($action['0']['_keep'] != "0")             {$keep = $action['0']['_keep']; }  else {$keep = 0;}
     if($action['0']['_place_on_farm'] != "0" )   {$place = "already"; $ActionP = "CHECKED"; $ActionN = "";} else {$place = "show"; }
     if($action['0']['_construction'] != "0")     {$extraText .= " Used for construction"; $placeable = "N";}
     if($action['0']['_consume'] != "0")          {$consume = 'Y'; $placeable = "N"; $ActionC = "CHECKED"; $ActionN = "";}
     if($action['0']['_place_in_build'] != "0")   {$extraText .= " Will be placed in building"; $placeable = "N";}
     if($action['0']['_place_in_special'] != "0") {$extraText .= " Will be used for special"; $placeable = "N";}
     if($action['0']['_selling'] != "0")          {$sell = "already"; $ActionS = "CHECKED"; $ActionN = "";}  else {$sell = "show";}
     if($action['0']['_collection'] != "0")       {$collection = "Y"; $placeable = "N";} else {$collection = "N";}
   }
   else
   {
     // no action yet.
     if($placeable == "Y") {$place = "show"; } else {$place = "no";}
     $sell = "show";
     $collection = "N";
   }
//format the input string.
$input ="&nbsp;";
     $GB_SellButon = 'No action availible from this screen.<br>';
     $GB_SellCheck = 'No action availible from this screen.<br>';
     $GB_Radio = 'Not from here<br>';
     if($sell == "show")
       { // need to show sell buton
        $GB_SellButon  = '<form action="'. GiftBox_URL .'" method="get">';
        $GB_SellButon .= '<input type="submit" value="Add to selling" class="button"/>';
        $GB_SellButon .= '<input type="hidden" name="add_sell_2" value="' . $code . '"/>';
        $GB_SellButon .= '</form>';
        $GB_SellCheck  = 'Sell:<input type="checkbox" name="S" value="' . $code . '" />';
        $GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBC . '" value="0" CHECKED/>';
        $GB_Radio .= ' Sell<input type="radio" name="' . $code . '' . $GBC . '" value="S" /> ';
       }
     if($sell == "already")
       { // already on selling list
         $GB_SellButon  = '<form action="'. GiftBox_URL .'" method="get">';
         $GB_SellButon .= '<input type="submit" value="Remove from selling" class="button"/>';
         $GB_SellButon .= '<input type="hidden" name="rem_sell_2" value="' . $code . '"/>';
         $GB_SellButon .= '</form>';
         $GB_SellCheck = 'Sell:<input type="checkbox" name="S" value="' . $code . '" CHECKED/>';
         $GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBC . '" value="0" />';
         $GB_Radio .= ' Sell<input type="radio" name="' . $code . '' . $GBC . '" value="S" CHECKED/> ';
       }

     $GB_PlaceButon = '&nbsp;';
     $GB_ConsumButon = '&nbsp;';
     $GB_PlaceCheck = '&nbsp;';
     $GB_PlaceRadio = '&nbsp;';
     if($place == "already")
         {
           $GB_PlaceButon  = '<form action="'. GiftBox_URL .'" method="get">';
           $GB_PlaceButon .= '<input type="submit" value="Remove from Place" class="button"/>';
           $GB_PlaceButon .= '<input type="hidden" name="rem_place_2" value="' . $code . '"/>';
           $GB_PlaceButon .= '</form>';
           $GB_PlaceCheck = 'Place:<input type="checkbox" name="P" value="' . $code . '" CHECKED/>';
           $GB_Radio .= ' Place<input type="radio" name="' . $code . '' . $GBC . '" value="P" CHECKED/>';
           //$GB_Radio .= '<input type="radio" name="P' . $GBC . '' . $code . '" value="0" />No ';
         }
     if($placeable == "N" ) { $GB_PlaceButon = ' '; $GB_PlaceCheck = ' '; }
     if($placeable == "Y" )
       {
          if($place == "show")
              { // Placeable && need to show
                $GB_PlaceButon  = '<form action="'. GiftBox_URL .'" method="get">';
                $GB_PlaceButon .= '<input type="submit" value="Add to Place" class="button"/>';
                $GB_PlaceButon .= '<input type="hidden" name="add_place_2" value="' . $code . '"/>';
                $GB_PlaceButon .= '</form>';
                $GB_PlaceCheck  = 'Place:<input type="checkbox" name="P" value="' . $code . '"/>';
                $GB_Radio .= ' Place<input type="radio" name="' . $code . '' . $GBC . '" value="P"/>';
                //$GB_PlaceRadio  = '| Place:Yes<input type="radio" name="P' . $GBC . '' . $code . '" value="Y"/>';
                //$GB_PlaceRadio .= '<input type="radio" name="P' . $GBC . '' . $code . '" value="0" CHECKED/>No ';
              }
       }
     if($type == "consumable")
       { //$ActionC = "CHECKED";
         if($consume == 'N')
           {
             $GB_ConsumButon  = '<form action="'. GiftBox_URL .'" method="get">';
             $GB_ConsumButon .= '<input type="submit" value="Add to consume" class="button"/>';
             $GB_ConsumButon .= '<input type="hidden" name="add_consume_2" value="' . $code . '"/>';
             $GB_ConsumButon .= '</form>';
             $extraText .= " Can be consumed.";
           }
           else
           {
             $GB_ConsumButon  = '<form action="'. GiftBox_URL .'" method="get">';
             $GB_ConsumButon .= '<input type="submit" value="Do NOT consume" class="button"/>';
             $GB_ConsumButon .= '<input type="hidden" name="add_consume_2" value="' . $code . '"/>';
             $GB_ConsumButon .= '</form>';
             $extraText .= " Will be consumed.";
           }
             $GB_ConsumButon .= '<form action="'. GiftBox_URL .'" method="get">';
             $GB_ConsumButon .= 'Keep:<input type="text" size="2" maxlength="2"  name="KEEPconsume" value="'.$keep.'" /> ';
             $GB_ConsumButon .= '<input type="hidden" name="KEEPcode" value="' . $code . '"/>';
             $GB_ConsumButon .= '<input type="submit" value="Change keep" class="button"/>';
             $GB_ConsumButon .= '</form>';

         $GB_Radio  = 'No action<input type="radio" name="' . $code . '' . $GBC . '" value="0" '.$ActionN.' />';
         $GB_Radio .= ' | Sell<input type="radio" name="' . $code . '' . $GBC . '" value="S" '.$ActionS.' /> ';
         $GB_Radio .= ' | Consume<input type="radio" name="' . $code . '' . $GBC . '" value="C" '.$ActionC.' /> ';
         $GB_Radio .= ' | Keep:<input type="text" size="2" maxlength="2"  name="' . $code . 'KEEP' . $GBC . '" value="'.$keep.'" /> ';
       }
     if($collection == "Y" )
       {
         $extraText .= " Collectable " ;
       }
    // now for all.
    $extraText .= " Keep " . $keep;

//  $GB_FormStart  = '<form action="'. GiftBox_URL .'" method="get">';
//  $GB_FormEnd  = '</form>';

//  if($what == "button")   { $input =  $GB_SellButon ." ".$GB_PlaceButon. " ".$GB_ConsumButon ." ".$extraText; }
  if($what == "button")   { $input =  $GB_SellButon ." ".$GB_PlaceButon. " ".$extraText; }
  if($what == "checkbox") { $input =  $GB_SellCheck . " ".$GB_PlaceCheck ." ".$extraText; }
  if($what == "radio")    { $input =  $GB_Radio ; }

return $input;
}


//================================================================================
// popup action function
//================================================================================
function GB_popupcontent1($unit, $action, $what)
{
 global $GBDBmain;
 global $GBDBuser;
 global $GBGetTemp;
 global $GB_Setting;

  $sell = "NO";          $place = "NO";         $sizeX = "0";          $sizeY = "0";
  if(array_key_exists('_code',$unit))
    {
      $code = $unit['_code'];
      $sizeX = $unit['_sizeX'];
      $sizeY = $unit['_sizeY'];
      $type = $unit['_type'];
      //$ = $unit['_'];
    }
    else
    {
      $code = $unit['0']['_code'];
      $sizeX = $unit['0']['_sizeX'];
      $sizeY = $unit['0']['_sizeY'];
      $type = $unit['0']['_type'];
    }

  $ActionN = "CHECKED";
  $ActionS = "";
  $ActionP = "";
  $ActionC = "";
  $keep = 0;
  $consume = 'N';
  //echo 'function code '. $code .'<br>';
  //set variable good.
  if($sizeX == ""){$sizeX = "0";}
  if($sizeY == ""){$sizeY = "0";}
  // can we place this item?
  $extraText = "";
  if($type == 'tree' ){$sizeX = "1";  $sizeY = "1";}
  if($sizeX == "1" && $sizeY == "1" ){$placeable = "Y";}else{$placeable = "N";}
  $place_in_build = 'N';
  $place_in_special = 'N';
  if (!empty($action))
   {
     if($action['0']['_keep'] != "0")             {$keep = $action['0']['_keep']; }  else {$keep = 0;}
     if($action['0']['_place_on_farm'] == "Y" )   {$place = "already"; $ActionP = "CHECKED"; $ActionN = "";} else {$place = "show"; }
     if($action['0']['_construction'] != "0")     {$extraText .= " Used for construction"; $placeable = "N";}
     if($action['0']['_consume'] == "Y")          {$consume = 'Y'; $placeable = "N"; $ActionC = "CHECKED"; $ActionN = "";}
     if($action['0']['_place_in_build'] != "0")   {$extraText .= " Will be placed in building"; $placeable = "N"; $place_in_build = 'Y';}
     if($action['0']['_place_in_special'] != "0") {$extraText .= " Will be used for special"; $placeable = "N"; $place_in_special = 'Y';}
     if($action['0']['_selling'] == "Y")          {$sell = "already"; $ActionS = "CHECKED"; $ActionN = "";}  else {$sell = "show";}
     if($action['0']['_collection'] != "0")       {$collection = "Y"; $placeable = "N";} else {$collection = "N";}
   }
   else
   {
     // no action yet.
     if($placeable == "Y") {$place = "show"; } else {$place = "no";}
     $sell = "show";
     $collection = "N";
   }
//format the input string.
$input ="&nbsp;";
     $GB_SellButon = 'No action availible from this screen.<br>';
     $GB_SellCheck  = 'Sell:<input type="checkbox" name="S" value="' . $code . '" '.$ActionS.' />'  ;

     $GB_PlaceCheck = '&nbsp;';
     if($place == "already")
         {
           $GB_PlaceCheck = 'Place:<input type="checkbox" name="P" value="' . $code . '" CHECKED/>';
         }
     if($placeable == "N" ) { $GB_PlaceCheck = 'Place: Not posible '; }
     if($placeable == "Y" )
       {
          if($place == "show")
              { // Placeable && need to show
                $GB_PlaceCheck  = 'Place:<input type="checkbox" name="P" value="' . $code . '"/>';
              }
       }

     if($type == "consumable")
       { //$ActionC = "CHECKED";
             $GB_ConsumCheck  = 'Consume:<input type="checkbox" name="C" value="' . $code . ' ' . $ActionC. ' "/>';
       } else {$GB_ConsumCheck  = 'Consume: Not posible';}

     if($collection == "Y" )
       { $GB_Collectable = 'This is a collectable item'; $extraText .= " Collectable " ;
       }else{$GB_Collectable = '';}

     $fuel = '';
     if($type == 'fuel' ){if($GB_Setting['DoFuel']){ $fuel = 'This is fuel. Fuel is enabled';}else{ $fuel = 'This is fuel. Fuel is disabled';}}

$input .=  '<form action="'. GiftBox_URL .'" method="get">';
$input .=  '<input type="hidden" name="popup" value="' . $code . '" /> ';     //
//$input .=  '<input type="hidden" name="time" value="' . $now . '" /> ';     //
$input .=  $GB_SellCheck   . "<br>" ;
$input .=  $GB_PlaceCheck  . "<br>" ;
$input .=  $GB_ConsumCheck . '<br>';
$input .=  $GB_Collectable . '<br>';
if($place_in_build == 'Y'){$input .='Remove from place in building:<input type="checkbox" name="RemPlaceInBuild" value="' . $code . '"/>';}
if($place_in_special == 'Y'){$input .='Remove from place in Special:<input type="checkbox" name="RemPlaceInSpecial" value="' . $code . '"/>';}
$input .=  $fuel . '<br>';
$input .= 'Keep in GiftBox:<input type="text" size="2" maxlength="2"  name="KEEP" value="'.$keep.'" /><br> ';
//$input .= '<input type="hidden" name="KEEPcode" value="' . $code . '"/>';
$input .= '<input type="submit" value="Change settings" class="button"/>';
$input .=  '';
$input .=  '</form>';
//$input .=  '<br>' . var_dump($action);
return $input;
}



//------------------------------------------------------------------------------
// GB_FindPets  SQL
//------------------------------------------------------------------------------
function GB_FindPetsSQL()
{
 global $GBDBuser;
 $i = 0;
    $GB_Pets = array();
    $GBSQL ="SELECT _set,_val,_obj FROM objects WHERE _obj IN (SELECT _obj FROM objects WHERE _set = 'className' AND _val = 'Pet')";
    $query = sqlite_query($GBDBuser, $GBSQL)  or GBSQLError($GBDBuser, $GBSQL);
    while ($entry = sqlite_fetch_array($query, SQLITE_ASSOC))
    {
      $GB_Pets[$entry['_obj']][$entry['_set']] = $entry['_val']  ;
      if($entry['_set'] == 'position')       {$GB_Pets[$entry['_obj']][$entry['_set']] = unserialize($entry['_val'])  ;}
      // What does it need to eat? Treat or Kibble?
      if ($entry['_set'] == 'petLevel')
         {
           $GB_Pets[$entry['_obj']]['FeedWhat'] = "treat";
           if($entry['_val']  == 0) { $GB_Pets[$entry['_obj']]['FeedWhat'] = "kibble"; }
         }
      // Feet time
      if($entry['_set'] == 'lastFedTime')
        {
           $FeedTime = $entry['_val'] / 1000;
           $FeedTime = $FeedTime + 86401 ;
           $GB_Pets[$entry['_obj']]['feedtime'] = $FeedTime;
        }
      $i++;
    }
  return $GB_Pets;
}

// ========================= nice time. Facebook style.
function nicetime($date)
{
    if(empty($date)) {
        return "No date provided";
    }

    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths         = array("60","60","24","7","4.35","12","10");

    $now             = time();
//    $unix_date       = strtotime($date);  // Do not provide real date, but unix timestamp
    $unix_date       = $date;

       // check validity of date
    if(empty($unix_date)) {
        return "Bad date";
    }

    // is it future date or past date
    if($now > $unix_date) {
        $difference     = $now - $unix_date;
        $tense         = "ago";

    } else {
        $difference     = $unix_date - $now;
        $tense         = "from now";
    }

    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }

    $difference = round($difference);

    if($difference != 1) {
        $periods[$j].= "s";
    }

    return "$difference $periods[$j] {$tense}";
}

function GB_AMF_Error($res)
{
AddLog2("GB oeps.. that was wrong.. : $res");
return;
}

// Check if the default?action.txt exist or was modified.
function GB_AutoActionFile()
{
  global $GBDBuser;
  global $GB_Setting;
  global $this_plugin;
$filename = 'default_actions.txt';
$filename = $this_plugin['folder'].'/actions/'.$filename ;
if (file_exists($filename))
   { $fileMtime = filemtime($filename);}
   else
   {AddLog2("No default action file found.");
    return 'No';}

if(array_key_exists('ActionFileTime' , $GB_Setting))
  {$fileLast = $GB_Setting['ActionFileTime'];}
  else
  {$fileLast = '0';}

if($fileMtime > $fileLast)
  {
    AddLog2("GB ===== Default action file found.");
    AddLog2("GB ===== Default action file is newer than last time.");
    AddLog2("GB ===== will import Default action file now.");
    $status = GB_import_action('ADD', $filename);
    AddLog2("GB ===== Import status." . $status);
    GB_Update_User_Setting("ActionFileTime" , $fileMtime);
  }
return;
}

//*****************************************************************************
// garage
// $what = html
// $what = hook
function GB_garage($what)
{
global $GBDBuser;

       $return = array();
       $n = 0;
       $return['0']['vehicle'] = $n;
       $return['0']['id'] = 0 ;
       $html = '';

       $GBSQL = "SELECT _obj FROM objects WHERE _set = 'itemName' AND _val = 'garage_finished'" ;
       $result = sqlite_query($GBDBuser, $GBSQL) or GBSQLError($GBDBuser, $GBSQL);
       $Objectbuildings = sqlite_fetch_all($result);
       if (sqlite_num_rows($result) == 0)
       {
         $html .= 'No garage found on the farm<br>';
       }
       else
       {
       $html .=   '<br><b>Garage found on farm</b> <br>';
           // Get the data from this object.
           $GBSQL = "SELECT _set,_val FROM objects WHERE _obj = '". $Objectbuildings['0']['_obj']. "'" ;
           $query = sqlite_query($GBDBuser, $GBSQL)  or GBSQLError($GBDBuser, $GBSQL);
            while ($entry = sqlite_fetch_array($query, SQLITE_ASSOC))
              {
                $TargetObject[$entry['_set']] = $entry['_val']  ;
                if($entry['_set'] == 'contents')       {$TargetObject[$entry['_set']] = unserialize($entry['_val'])  ;}
                if($entry['_set'] == 'expansionParts') {$TargetObject[$entry['_set']] = unserialize($entry['_val'])  ;}
                if($entry['_set'] == 'position')       {$TargetObject[$entry['_set']] = unserialize($entry['_val'])  ;}
              }
           if(!array_key_exists('isFullyBuilt', $TargetObject)){$TargetObject['isFullyBuilt'] = "N";}
           if( $TargetObject['isFullyBuilt'] == "1"   )
           {
             $return['0']['id'] = $TargetObject['id'] ;
             foreach($TargetObject['contents'] as $vehicle )
               {
                 $n++;
                 $return['0']['vehicle'] = $n;
                 $Vneedmax = 32;
                // if($vehicle['itemCode'] == 'NY') {$Vneedmax = 25;} // school bus
                // if($vehicle['itemCode'] == 'TZ') {$Vneedmax = 25;} // tractorhotrod_cash
                // if($vehicle['itemCode'] == 'RZ') {$Vneedmax = 25;} // harvesterhotrod_cash
                // if($vehicle['itemCode'] == 'SJ') {$Vneedmax = 25;} // seederhotrod_cash
                 if($vehicle['numParts'] >= $Vneedmax)
                 {
                    $html .=  'Garage contains ' . $vehicle['itemCode'] . ' fully upgraded ('. $vehicle['numParts'] .' parts)<br>';
                 }
                 else
                 {
                    $html .=  'Garage contains ' . $vehicle['itemCode'] . ' With ' . $vehicle['numParts'] . ' vehicle parts. Need ' . ($Vneedmax-$vehicle['numParts']) . ' more parts<br>';
                    //$return[$vehicle['itemCode']] = ($Vneedmax-$vehicle['numParts']);
                    $return[$n] = array('itemCode'=> $vehicle['itemCode'], 'numParts' => $vehicle['numParts'] , 'need' => ($Vneedmax-$vehicle['numParts']));
                    //$return['vehicle'] = $n;
                 }
               }
           }
           else
           {
              $html .=  'No garage is not fully build<br>';
              $return['vehicle'] = 0;
           }

      }
 if ($what == 'html' )
 {
  return $html;
 }
 else
 {
  return $return;
 }

}


function GB_CanWeStore($Unit)
{
$Able2Store = 'N';
   $nonStorableClass = array('CCrafted','Collection', 'Equipment', 'FlowerDecoration', 'LootableDecoration', 'StorageBuilding', 'InventoryCellar', 'HolidayTreeStorage', 'ValentinesPresent', 'Pet', 'BuildingPart', 'ShovelItem', 'MysteryGift', 'SocialPlumbingMysteryGift');
   $nonStorableSubtype = array('animal_pens', 'crafting', 'storage');
   // check if decoration
     if ($Unit['_type'] == 'decoration' )
        { $Able2Store = 'Y';
        }
     if ($Unit['_type'] == 'building' )
        { $Able2Store = 'Y';
        }
     if(in_array($Unit['_className'], $nonStorableClass))  {$Able2Store = 'N';}
     if(in_array($Unit['_subtype'], $nonStorableSubtype))  {$Able2Store = 'N';}

return $Able2Store;
}


?>

